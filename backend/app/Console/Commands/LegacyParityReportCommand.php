<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AchTransfer;
use App\Models\AiThread;
use App\Models\Area;
use App\Models\Closing;
use App\Models\Communication;
use App\Models\Document;
use App\Models\Fdd;
use App\Models\FieldValue;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\Organization;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

final class LegacyParityReportCommand extends Command
{
    protected $signature = 'legacy:parity-report
                            {dump? : Path to .sql.gz dump}
                            {--prefix=vnzokz0zw_9_ : Legacy dump table prefix}
                            {--samples : Include post-import field/value spot checks}';

    protected $description = 'Compare FIL entity counts against legacy dump inventory.';

    public function handle(): int
    {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) $this->option('prefix');
        $script = base_path('../tools/inventory-dump.php');

        if (! is_readable($script)) {
            $this->error("Inventory script missing: {$script}");

            return self::FAILURE;
        }

        $process = new Process(['php', $script, $dump, "--prefix={$prefix}"]);
        $process->setTimeout(600);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->warn('Legacy inventory failed or dump unreadable — row counts skipped.');
        } else {
            $legacy = $this->parseLegacyCounts($process->getOutput());

            $rows = [
                ['Entity', 'FIL', 'Legacy (approx)', 'Delta'],
                ['users', User::query()->count(), $legacy['users'] ?? '—', $this->delta(User::query()->count(), $legacy['users'] ?? null)],
                ['leads', Lead::query()->count(), $legacy['application'] ?? '—', $this->delta(Lead::query()->count(), $legacy['application'] ?? null)],
                ['stores', Store::query()->count(), $legacy['store'] ?? '—', $this->delta(Store::query()->count(), $legacy['store'] ?? null)],
                ['areas', Area::query()->count(), $legacy['area'] ?? '—', $this->delta(Area::query()->count(), $legacy['area'] ?? null)],
                ['fdds', Fdd::query()->count(), ($legacy['grabbafdd'] ?? 0) + ($legacy['areafdd'] ?? 0), '—'],
                ['organizations', Organization::query()->count(), $legacy['organization'] ?? '—', $this->delta(Organization::query()->count(), $legacy['organization'] ?? null)],
                ['closings', Closing::query()->count(), $legacy['closing'] ?? '—', $this->delta(Closing::query()->count(), $legacy['closing'] ?? null)],
                ['communications', Communication::query()->count(), $legacy['z_communications'] ?? '—', $this->delta(Communication::query()->count(), $legacy['z_communications'] ?? null)],
                ['notification_rules', NotificationRule::query()->count(), $legacy['notifications'] ?? '—', $this->delta(NotificationRule::query()->count(), $legacy['notifications'] ?? null)],
                ['ai_threads', AiThread::query()->count(), $legacy['zai_chats'] ?? '—', $this->delta(AiThread::query()->count(), $legacy['zai_chats'] ?? null)],
                ['royalty_periods', RoyaltyPeriod::query()->count(), $legacy['weekly_store_revenue'] ?? '—', $this->delta(RoyaltyPeriod::query()->count(), $legacy['weekly_store_revenue'] ?? null)],
                ['ach_transfers', AchTransfer::query()->count(), $legacy['ach_transfers'] ?? '—', $this->delta(AchTransfer::query()->count(), $legacy['ach_transfers'] ?? null)],
            ];

            $this->table($rows[0], array_slice($rows, 1));
        }

        if ($this->option('samples')) {
            $this->renderSampleChecks();
        }

        $this->info('Run `php artisan legacy:import --execute` then `legacy:finalize` to close gaps.');
        $this->line('Run `php artisan legacy:mapping-gaps --entity=store` to audit unmapped postmeta keys.');

        return self::SUCCESS;
    }

    private function renderSampleChecks(): void
    {
        $this->newLine();
        $this->line('<fg=cyan>Post-import spot checks</>');

        $leadTotal = Lead::query()->count();
        $storeTotal = Store::query()->count();

        $checks = [
            ['Leads with custom field_values', $this->entitiesWithFieldValues('lead'), $leadTotal],
            ['Leads with interest_region_id', Lead::query()->whereNotNull('interest_region_id')->count(), $leadTotal],
            ['Leads with staged extras', $this->entitiesWithExtras(Lead::class), $leadTotal],
            ['Stores with store_status', Store::query()->whereNotNull('store_status')->where('store_status', '!=', '')->count(), $storeTotal],
            ['Stores with custom field_values', $this->entitiesWithFieldValues('store'), $storeTotal],
            ['Stores with staged extras', $this->entitiesWithExtras(Store::class), $storeTotal],
            ['Imported documents', Document::query()->count(), null],
        ];

        $sampleRows = [];

        foreach ($checks as [$label, $count, $total]) {
            $pct = ($total !== null && $total > 0)
                ? sprintf('%.1f%%', ($count / $total) * 100)
                : '—';
            $sampleRows[] = [$label, (string) $count, $pct];
        }

        $this->table(['Check', 'Count', '% of entity'], $sampleRows);

        if ($this->entitiesWithExtras(Lead::class) > 0 || $this->entitiesWithExtras(Store::class) > 0) {
            $this->warn('Staged extras remain — run `php artisan legacy:drain-extras --execute` and `legacy:finalize --strict`.');
        }
    }

    private function entitiesWithFieldValues(string $entityType): int
    {
        return (int) FieldValue::query()
            ->where('entity_type', $entityType)
            ->distinct()
            ->count('entity_id');
    }

    /**
     * @param  class-string<Lead|Store>  $modelClass
     */
    private function entitiesWithExtras(string $modelClass): int
    {
        $table = (new $modelClass)->getTable();

        return (int) DB::table($table)
            ->whereNotNull('extras')
            ->where('extras', '!=', '[]')
            ->where('extras', '!=', '{}')
            ->where('extras', '!=', 'null')
            ->count();
    }

    /**
     * @return array<string, int>
     */
    private function parseLegacyCounts(string $output): array
    {
        $counts = [];

        foreach (explode("\n", $output) as $line) {
            if (preg_match('/^\s*(\w+):\s*(\d+)/', $line, $m)) {
                $counts[$m[1]] = (int) $m[2];
            }

            if (preg_match('/^\s*-\s*(\w+):\s*(\d+)/', $line, $m)) {
                $counts[$m[1]] = (int) $m[2];
            }
        }

        return $counts;
    }

    private function delta(int $fil, ?int $legacy): string
    {
        if ($legacy === null) {
            return '—';
        }

        return (string) ($fil - $legacy);
    }
}
