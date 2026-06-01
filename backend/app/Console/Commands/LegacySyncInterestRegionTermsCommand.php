<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyInterestRegionTermSyncService;
use Illuminate\Console\Command;

final class LegacySyncInterestRegionTermsCommand extends Command
{
    protected $signature = 'legacy:sync-interest-region-terms
                            {dump? : Path to .sql.gz dump}
                            {--prefix= : Legacy dump table prefix}
                            {--execute : Persist legacy_term_id links and create missing market regions}';

    protected $description = 'Map grabba_tax_area WordPress terms to interest_regions (legacy_term_id).';

    public function handle(LegacyInterestRegionTermSyncService $service): int
    {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) ($this->option('prefix') ?: config('fil.legacy.table_prefix'));
        $execute = (bool) $this->option('execute');

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        if (! $execute) {
            $this->warn('Dry run — pass --execute to write legacy_term_id values and create market regions.');
        }

        $stats = $service->sync($dump, $prefix, $execute);

        $this->table(['Metric', 'Count'], [
            ['grabba_tax_area terms', (string) $stats['terms']],
            ['linked to seeded regions', (string) $stats['linked']],
            ['created market regions', (string) $stats['created']],
            ['already linked (skipped)', (string) $stats['skipped']],
            ['unresolved', (string) $stats['unresolved']],
        ]);

        if ($stats['unresolved'] > 0) {
            $this->warn('Some legacy terms could not be mapped — review country/parent rows in the dump.');

            return self::FAILURE;
        }

        $this->info($execute
            ? 'Interest region legacy term sync complete.'
            : 'Dry run complete — re-run with --execute before legacy postmeta import.');

        return self::SUCCESS;
    }
}
