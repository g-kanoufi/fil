<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyAcfImportService;
use Illuminate\Console\Command;

final class LegacyImportAcfCommand extends Command
{
    protected $signature = 'legacy:import-acf
                            {path? : Directory containing ACF JSON group files}
                            {--entity=lead : Default entity when group is not in fil.legacy.acf_groups}';

    protected $description = 'Import ACF field group JSON into field_groups and fields tables.';

    public function handle(LegacyAcfImportService $importer): int
    {
        $path = $this->argument('path')
            ?? (string) config('fil.legacy.acf_path');

        if (! is_dir($path)) {
            $this->error("ACF path not found: {$path}");

            return self::FAILURE;
        }

        $result = $importer->importDirectory($path, (string) $this->option('entity'));

        $this->info("Imported {$result['groups']} field groups, {$result['fields']} fields, skipped {$result['skipped_groups']} out-of-scope groups from {$path}");

        return self::SUCCESS;
    }
}
