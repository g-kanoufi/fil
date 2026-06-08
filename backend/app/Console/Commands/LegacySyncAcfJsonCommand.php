<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

final class LegacySyncAcfJsonCommand extends Command
{
    protected $signature = 'legacy:sync-acf-json
                            {source? : Directory containing group_*.json (default: FIL_LEGACY_ACF_SOURCE)}
                            {--target= : Destination directory (default: resources/legacy-acf)}
                            {--dry-run : List files without copying}';

    protected $description = 'Copy z-acf-sync field group JSON into bundled legacy-acf path.';

    public function handle(): int
    {
        $source = $this->argument('source')
            ?? (string) config('fil.legacy.acf_source_path', '');

        if ($source === '' || ! is_dir($source)) {
            $this->error('Source directory not found. Pass path or set FIL_LEGACY_ACF_SOURCE.');

            return self::FAILURE;
        }

        $target = (string) ($this->option('target') ?: base_path('resources/legacy-acf'));

        if (! $this->option('dry-run') && ! File::isDirectory($target)) {
            File::makeDirectory($target, 0755, true);
        }

        $copied = 0;
        $skipped = 0;

        foreach (glob($source.'/group_*.json') ?: [] as $file) {
            $basename = basename($file);
            $dest = $target.'/'.$basename;

            if ($this->option('dry-run')) {
                $this->line("Would copy: {$basename}");
                $copied++;

                continue;
            }

            if (is_readable($dest) && md5_file($file) === md5_file($dest)) {
                $skipped++;

                continue;
            }

            if (! copy($file, $dest)) {
                $this->error("Failed to copy {$basename}");

                return self::FAILURE;
            }

            $copied++;
        }

        $mode = $this->option('dry-run') ? 'Would copy' : 'Copied';
        $this->info("{$mode} {$copied} group file(s) to {$target} (skipped {$skipped} unchanged).");
        $this->line('Next: php artisan legacy:import-acf');

        return self::SUCCESS;
    }
}
