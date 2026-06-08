<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyAcfImportService;
use App\Services\Legacy\LegacyFieldPostTypeBackfillService;
use Illuminate\Console\Command;

final class LegacyImportAcfCommand extends Command
{
    protected $signature = 'legacy:import-acf
                            {path? : Directory containing group_*.json ACF exports}';

    protected $description = 'Import ACF field group JSON into field_groups and fields tables.';

    public function handle(LegacyAcfImportService $importer, LegacyFieldPostTypeBackfillService $backfill): int
    {
        $path = $this->argument('path')
            ?? (string) config('fil.legacy.acf_path');

        if (! is_dir($path)) {
            $this->error("ACF path not found: {$path}");

            return self::FAILURE;
        }

        $result = $importer->importDirectory($path);
        $backfilled = $backfill->backfill(true);

        $this->info("Imported {$result['groups']} field groups, {$result['fields']} fields, skipped {$result['skipped_groups']} out-of-scope groups from {$path}");
        $this->info("Backfilled legacy_post_type on {$backfilled['fields']} fields in {$backfilled['groups']} groups.");

        return self::SUCCESS;
    }
}
