<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyImportBaselineCompareService;
use Illuminate\Console\Command;

final class LegacyImportBaselineCompareCommand extends Command
{
    protected $signature = 'legacy:import-baseline-compare
                            {--golden= : Path to golden JSON fixture}
                            {--import : Import posts + postmeta fixture before comparing}
                            {--fixture= : SQL fixture path (required with --import)}
                            {--prefix=wp_9_ : Legacy dump table prefix}';

    protected $description = 'Compare imported tier-1 columns and field_values against a golden JSON contract.';

    public function handle(LegacyImportBaselineCompareService $service): int
    {
        $golden = (string) ($this->option('golden') ?: base_path('tests/fixtures/legacy-import-baseline-golden.json'));

        if ($this->option('import')) {
            $fixture = (string) ($this->option('fixture') ?? '');

            if ($fixture === '' || ! is_readable($fixture)) {
                $this->error('Pass --fixture= with a readable SQL file when using --import.');

                return self::FAILURE;
            }

            $prefix = (string) $this->option('prefix');

            $this->call('legacy:import', [
                'dump' => $fixture,
                '--prefix' => $prefix,
                '--only' => 'leads,postmeta',
                '--execute' => true,
            ]);
        }

        $result = $service->compare($golden);

        if ($result['ok']) {
            $this->info('Baseline compare passed.');

            return self::SUCCESS;
        }

        $this->error('Baseline compare failed:');

        foreach ($result['diffs'] as $diff) {
            $this->line("  - {$diff}");
        }

        return self::FAILURE;
    }
}
