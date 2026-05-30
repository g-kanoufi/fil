<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Drips\SendDripStepJob;
use App\Models\DripCampaign;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Models\DripStepRun;
use App\Models\Lead;
use App\Models\User;
use App\Services\Drips\DripEnrollmentService;
use App\Services\Mail\OutboundMailGuard;
use App\Services\Sms\TwilioSmsService;
use Database\Seeders\DripCampaignSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

final class MvpSmokeDripCommand extends Command
{
    protected $signature = 'mvp:smoke-drip
                            {--sink=dev@fil.test : Safe sink address for non-production mail}
                            {--mailhog=http://127.0.0.1:8025 : Mailhog API base URL}';

    protected $description = 'Send one drip email step through Mailhog (MVP ops smoke test)';

    public function handle(OutboundMailGuard $mailGuard, TwilioSmsService $twilio, DripEnrollmentService $drips): int
    {
        $sink = (string) $this->option('sink');
        $mailhog = rtrim((string) $this->option('mailhog'), '/');

        $this->configureMailhogTransport($sink);

        $guard = $mailGuard->status();
        $this->table(['Setting', 'Value'], [
            ['Guarded', $guard['guarded'] ? 'yes' : 'no'],
            ['Effective mailer', $guard['effective_mailer']],
            ['Sink addresses', implode(', ', $guard['sink_addresses'])],
        ]);

        if (! $this->mailhogReachable($mailhog)) {
            $this->error("Mailhog is not reachable at {$mailhog}. Run `docker compose up -d mailhog`.");

            return self::FAILURE;
        }

        $beforeCount = $this->mailhogMessageCount($mailhog);

        $this->call('db:seed', ['--class' => DripCampaignSeeder::class, '--force' => true]);

        $campaign = DripCampaign::query()->where('slug', 'new-lead-welcome')->firstOrFail();
        $step = DripStep::query()
            ->where('drip_campaign_id', $campaign->id)
            ->where('channel', 'email')
            ->firstOrFail();

        $prospect = User::query()->firstOrCreate(
            ['email' => 'drip-smoke-prospect@fil.test'],
            User::factory()->make([
                'email' => 'drip-smoke-prospect@fil.test',
                'first_name' => 'Drip',
                'last_name' => 'Smoke',
            ])->getAttributes(),
        );

        $lead = Lead::query()->firstOrCreate(
            ['title' => 'Drip Smoke Application'],
            Lead::factory()->make([
                'prospect_user_id' => $prospect->id,
                'title' => 'Drip Smoke Application',
                'pipeline_phase' => 1,
            ])->getAttributes(),
        );

        $enrollment = DripEnrollment::query()->create([
            'drip_campaign_id' => $campaign->id,
            'lead_id' => $lead->id,
            'status' => 'active',
        ]);

        $run = DripStepRun::query()->create([
            'drip_enrollment_id' => $enrollment->id,
            'drip_step_id' => $step->id,
            'scheduled_at' => now(),
            'status' => 'pending',
        ]);

        (new SendDripStepJob($run->id))->handle($twilio, $drips);

        $run->refresh();

        if ($run->status !== 'sent') {
            $this->error('Drip step did not send. Status: '.$run->status.' Error: '.($run->error ?? 'none'));

            return self::FAILURE;
        }

        $afterCount = $this->mailhogMessageCount($mailhog);

        if ($afterCount <= $beforeCount) {
            $this->error('No new message appeared in Mailhog.');

            return self::FAILURE;
        }

        $this->info("Drip step sent (communication #{$run->communication_id}). Mailhog messages: {$beforeCount} → {$afterCount}.");
        $this->line("Open {$mailhog} to inspect the message delivered to sink [{$sink}].");

        return self::SUCCESS;
    }

    private function configureMailhogTransport(string $sink): void
    {
        config([
            'fil-mail.sink_addresses' => [$sink],
            'fil-mail.non_production_mailer' => 'smtp',
            'mail.default' => 'smtp',
            'mail.mailers.smtp.host' => '127.0.0.1',
            'mail.mailers.smtp.port' => 1025,
            'mail.mailers.smtp.encryption' => null,
            'mail.mailers.smtp.username' => null,
            'mail.mailers.smtp.password' => null,
        ]);
    }

    private function mailhogReachable(string $mailhog): bool
    {
        try {
            return Http::timeout(3)->get("{$mailhog}/api/v2/messages")->successful();
        } catch (\Throwable) {
            return false;
        }
    }

    private function mailhogMessageCount(string $mailhog): int
    {
        $response = Http::timeout(3)->get("{$mailhog}/api/v2/messages");

        if (! $response->successful()) {
            return 0;
        }

        return (int) ($response->json('total') ?? 0);
    }
}
