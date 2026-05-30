<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Exceptions\RoleDoesNotExist;
use Spatie\Permission\Models\Permission;

final class MvpStagingCheckCommand extends Command
{
    protected $signature = 'mvp:staging-check';

    protected $description = 'Verify staging/production prerequisites before go-live smoke tests';

    /**
     * @var list<string>
     */
    private const DEMO_EMAILS = [
        'admin@fil.test',
        'franchisor@fil.test',
        'owner@fil.test',
        'area_rep@fil.test',
        'franchisee@fil.test',
        'storemanager@fil.test',
        'employee@fil.test',
        'prospect@fil.test',
    ];

    public function handle(OutboundMailGuard $mailGuard): int
    {
        $checks = [
            $this->checkDatabase(),
            $this->checkMigrations(),
            $this->checkRoles(),
            $this->checkQueue(),
            $this->checkFailedJobs(),
            $this->checkMail($mailGuard),
            $this->checkAppUrl(),
            $this->checkSecurity(),
            $this->checkSessionCookies(),
            $this->checkDemoUsers(),
            $this->checkEmbedKeys(),
            $this->checkSanctumDomains(),
            $this->checkMailgunWebhook(),
            $this->checkDwollaWebhook(),
        ];

        $this->table(['Check', 'Status', 'Detail'], $checks);

        $failed = collect($checks)->contains(fn (array $row): bool => $row[1] === 'FAIL');

        if ($failed) {
            $this->error('Staging check failed — resolve FAIL rows before go-live.');

            return self::FAILURE;
        }

        $warned = collect($checks)->contains(fn (array $row): bool => $row[1] === 'WARN');

        if ($warned) {
            $this->warn('Staging check passed with warnings — review WARN rows before production cutover.');
        } else {
            $this->info('Staging check passed.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return ['Database', 'OK', DB::connection()->getDriverName()];
        } catch (\Throwable $exception) {
            return ['Database', 'FAIL', $exception->getMessage()];
        }
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkMigrations(): array
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('leads')) {
            return ['Migrations', 'FAIL', 'Core tables missing — run php artisan migrate'];
        }

        return ['Migrations', 'OK', 'Core tables present'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkRoles(): array
    {
        $permissionCount = Permission::query()->count();

        try {
            $adminExists = User::query()->role('admin')->exists();
        } catch (RoleDoesNotExist) {
            return [
                'Roles & permissions',
                'FAIL',
                'Run RolesAndPermissionsSeeder — admin role missing',
            ];
        }

        if ($permissionCount < 10 || ! $adminExists) {
            return [
                'Roles & permissions',
                'FAIL',
                'Run RolesAndPermissionsSeeder and create at least one admin user',
            ];
        }

        return ['Roles & permissions', 'OK', "{$permissionCount} permissions, admin user present"];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkQueue(): array
    {
        $driver = (string) config('queue.default', 'sync');

        if ($driver === 'sync') {
            return ['Queue worker', 'WARN', 'QUEUE_CONNECTION=sync — use database in staging/prod'];
        }

        if (! Schema::hasTable('jobs')) {
            return ['Queue worker', 'FAIL', 'jobs table missing — run queue:table migration'];
        }

        return ['Queue worker', 'OK', "Driver: {$driver}"];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkFailedJobs(): array
    {
        if (! Schema::hasTable('failed_jobs')) {
            return ['Failed jobs table', 'WARN', 'failed_jobs missing — queue failures will not persist'];
        }

        $failedCount = (int) DB::table('failed_jobs')->count();

        if ($failedCount > 0) {
            return ['Failed jobs table', 'WARN', "{$failedCount} failed job(s) — run queue:failed"];
        }

        return ['Failed jobs table', 'OK', 'No failed jobs'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkMail(OutboundMailGuard $mailGuard): array
    {
        $guard = $mailGuard->status();
        $mailer = (string) config('mail.default');

        if ($mailer === 'log' && app()->environment('production')) {
            return ['Mail delivery', 'FAIL', 'MAIL_MAILER=log is not allowed in production'];
        }

        return [
            'Mail delivery',
            'OK',
            sprintf(
                'mailer=%s guarded=%s sinks=%s',
                $guard['effective_mailer'],
                $guard['guarded'] ? 'yes' : 'no',
                implode(', ', $guard['sink_addresses']),
            ),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkAppUrl(): array
    {
        $url = (string) config('app.url');

        if ($url === '' || str_contains($url, 'localhost')) {
            return ['APP_URL', 'WARN', $url !== '' ? $url : 'not set'];
        }

        return ['APP_URL', 'OK', $url];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkSecurity(): array
    {
        if (app()->environment('production') && (bool) config('app.debug')) {
            return ['APP_DEBUG', 'FAIL', 'APP_DEBUG must be false in production'];
        }

        return ['APP_DEBUG', 'OK', config('app.debug') ? 'true (non-prod)' : 'false'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkSessionCookies(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Session cookies', 'OK', 'Skipped outside staging/production'];
        }

        if (! filter_var(config('session.secure'), FILTER_VALIDATE_BOOL)) {
            return [
                'Session cookies',
                'FAIL',
                'Set SESSION_SECURE_COOKIE=true for HTTPS-only session cookies',
            ];
        }

        $sameSite = strtolower((string) config('session.same_site', ''));

        if (! in_array($sameSite, ['lax', 'strict', 'none'], true)) {
            return [
                'Session cookies',
                'WARN',
                'Set SESSION_SAME_SITE=lax (or strict) for CSRF protection',
            ];
        }

        return ['Session cookies', 'OK', 'Secure + SameSite configured'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkDemoUsers(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Demo accounts', 'OK', 'Skipped outside staging/production'];
        }

        $demoCount = User::query()->whereIn('email', self::DEMO_EMAILS)->count();

        if ($demoCount > 0) {
            return [
                'Demo accounts',
                'FAIL',
                "{$demoCount} @fil.test demo user(s) still present — remove before go-live",
            ];
        }

        return ['Demo accounts', 'OK', 'No seeded demo accounts'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkEmbedKeys(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Embed site keys', 'OK', 'Skipped outside staging/production'];
        }

        $keys = config('fil.embed.site_keys', []);

        if (! is_array($keys) || $keys === []) {
            return [
                'Embed site keys',
                'FAIL',
                'Set client-specific FIL_EMBED_SITE_KEYS',
            ];
        }

        if (in_array('pk_dev', $keys, true)) {
            return [
                'Embed site keys',
                'FAIL',
                'Replace pk_dev with client-specific FIL_EMBED_SITE_KEYS',
            ];
        }

        return ['Embed site keys', 'OK', 'Client keys configured'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkSanctumDomains(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Sanctum domains', 'OK', 'Skipped outside staging/production'];
        }

        $domains = config('sanctum.stateful', []);
        $domainList = is_array($domains) ? implode(',', $domains) : (string) $domains;
        $appHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if ($domainList === '' || ($appHost && ! str_contains($domainList, (string) $appHost))) {
            return [
                'Sanctum domains',
                'WARN',
                'SANCTUM_STATEFUL_DOMAINS should include APP_URL host',
            ];
        }

        return ['Sanctum domains', 'OK', $domainList];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkMailgunWebhook(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Mailgun webhook', 'OK', 'Skipped outside staging/production'];
        }

        $mailer = (string) config('mail.default');

        if ($mailer !== 'mailgun') {
            return ['Mailgun webhook', 'OK', "Mailer is {$mailer}"];
        }

        if (! filled(config('services.mailgun.webhook_signing_key'))) {
            return [
                'Mailgun webhook',
                'WARN',
                'MAILGUN_WEBHOOK_SIGNING_KEY not set — delivery status updates disabled',
            ];
        }

        return ['Mailgun webhook', 'OK', 'Signing key configured'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkDwollaWebhook(): array
    {
        if (! app()->environment('production', 'staging')) {
            return ['Dwolla webhook', 'OK', 'Skipped outside staging/production'];
        }

        if (! filled(config('services.dwolla.webhook_secret'))) {
            return [
                'Dwolla webhook',
                'FAIL',
                'DWOLLA_WEBHOOK_SECRET not set — ACH status webhooks cannot be verified',
            ];
        }

        return ['Dwolla webhook', 'OK', 'Webhook secret configured'];
    }
}
