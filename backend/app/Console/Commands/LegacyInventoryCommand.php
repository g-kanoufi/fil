<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

final class LegacyInventoryCommand extends Command
{
    protected $signature = 'legacy:inventory
                            {dump? : Path to .sql.gz dump}
                            {--prefix=vnzokz0zw_9_ : Legacy dump table prefix for site 9}';

    protected $description = 'Run FIL legacy dump inventory (post types, custom tables).';

    public function handle(): int
    {
        $dump = $this->argument('dump') ?? base_path('../data/local.sql.gz');
        $prefix = (string) $this->option('prefix');
        $script = base_path('../tools/inventory-dump.php');

        if (! is_readable($script)) {
            $this->error("Inventory script missing: {$script}");

            return self::FAILURE;
        }

        $process = new Process(['php', $script, $dump, "--prefix={$prefix}"]);
        $process->setTimeout(600);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        return $process->isSuccessful() ? self::SUCCESS : self::FAILURE;
    }
}
