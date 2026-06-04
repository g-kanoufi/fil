<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Post-import spot check: sample leads/stores with tier-1 fields for Zorzees parity review.
 */
final class LegacySpotCheckCommand extends Command
{
    protected $signature = 'legacy:spot-check
                            {--leads=10 : Number of leads to sample}
                            {--stores=5 : Number of stores to sample}
                            {--fail-on-extras : Exit 1 if any lead/store has non-empty extras JSON}';

    protected $description = 'Sample leads and stores with key fields after legacy import (Zorzees parity review).';

    public function handle(): int
    {
        $leadLimit = max(1, (int) $this->option('leads'));
        $storeLimit = max(1, (int) $this->option('stores'));

        $leadTotal = Lead::query()->count();
        $storeTotal = Store::query()->count();

        if ($leadTotal === 0 && $storeTotal === 0) {
            $this->warn('No leads or stores in database. Run `php artisan legacy:import --execute` first.');

            return self::FAILURE;
        }

        $this->info("FIL database: {$leadTotal} leads, {$storeTotal} stores.");
        $this->newLine();

        if ($leadTotal > 0) {
            $this->line('<fg=cyan>Lead samples</> (compare lead_status / owner / interest_region to Zorzees Applications)');
            $leads = Lead::query()
                ->with(['owner:id,name,email', 'interestRegion:id,name'])
                ->orderByDesc('updated_at')
                ->limit($leadLimit)
                ->get();

            $rows = [];
            foreach ($leads as $lead) {
                $rows[] = [
                    (string) $lead->id,
                    $lead->legacy_post_id !== null ? (string) $lead->legacy_post_id : '—',
                    (string) ($lead->lead_status ?? '—'),
                    (string) ($lead->lead_stage ?? '—'),
                    $lead->owner?->name ?? '—',
                    $lead->interestRegion?->name ?? '—',
                    $this->extrasFlag($lead->extras),
                ];
            }

            $this->table(
                ['id', 'legacy_post', 'lead_status', 'lead_stage', 'owner', 'interest_region', 'extras'],
                $rows,
            );
        }

        if ($storeTotal > 0) {
            $this->newLine();
            $this->line('<fg=cyan>Store samples</> (compare store_status to Zorzees Locations/Units — not lead_status)');
            $stores = Store::query()
                ->with(['area:id,name'])
                ->orderByDesc('updated_at')
                ->limit($storeLimit)
                ->get();

            $rows = [];
            foreach ($stores as $store) {
                $rows[] = [
                    (string) $store->id,
                    $store->legacy_post_id !== null ? (string) $store->legacy_post_id : '—',
                    (string) ($store->store_status ?? '—'),
                    $store->area?->name ?? '—',
                    (string) ($store->name ?? '—'),
                    $this->extrasFlag($store->extras),
                ];
            }

            $this->table(
                ['id', 'legacy_post', 'store_status', 'area', 'name', 'extras'],
                $rows,
            );
        }

        $extrasLeads = $this->countWithExtras(Lead::class);
        $extrasStores = $this->countWithExtras(Store::class);

        if ($extrasLeads > 0 || $extrasStores > 0) {
            $this->warn("Staged extras: {$extrasLeads} leads, {$extrasStores} stores — run legacy:drain-extras --execute && legacy:finalize --strict");

            if ($this->option('fail-on-extras')) {
                return self::FAILURE;
            }
        } else {
            $this->info('No staged extras on leads/stores.');
        }

        $this->newLine();
        $this->line('Full counts: php artisan legacy:parity-report ../data/local.sql.gz --samples');

        return self::SUCCESS;
    }

    private function extrasFlag(mixed $extras): string
    {
        if ($extras === null) {
            return 'ok';
        }

        $encoded = is_string($extras) ? $extras : json_encode($extras);
        if ($encoded === null || $encoded === '' || $encoded === '[]' || $encoded === '{}' || $encoded === 'null') {
            return 'ok';
        }

        return 'STAGED';
    }

    /**
     * @param  class-string<Lead|Store>  $modelClass
     */
    private function countWithExtras(string $modelClass): int
    {
        $table = (new $modelClass)->getTable();
        $query = DB::table($table)->whereNotNull('extras');

        if (DB::connection()->getDriverName() === 'pgsql') {
            return (int) $query
                ->whereRaw("extras::text NOT IN ('[]', '{}', 'null')")
                ->count();
        }

        return (int) $query
            ->where('extras', '!=', '[]')
            ->where('extras', '!=', '{}')
            ->where('extras', '!=', 'null')
            ->count();
    }
}
