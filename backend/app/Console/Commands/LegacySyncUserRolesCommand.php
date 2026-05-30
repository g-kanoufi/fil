<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyUserImportService;
use Illuminate\Console\Command;

final class LegacySyncUserRolesCommand extends Command
{
    protected $signature = 'legacy:sync-user-roles
                            {dump? : Path to .sql.gz dump}
                            {--prefix= : Legacy dump table prefix}
                            {--execute : Apply role changes (default is dry-run)}';

    protected $description = 'Re-sync FIL roles from legacy capabilities in the dump.';

    public function handle(LegacyUserImportService $userImporter): int
    {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) ($this->option('prefix') ?: config('fil.legacy.table_prefix'));
        $execute = (bool) $this->option('execute');

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        if (! $execute) {
            $this->warn('Dry run — pass --execute to update roles.');
        }

        $stats = $userImporter->syncRoles($dump, $prefix, $execute);

        $this->table(['Metric', 'Count'], [
            ['profiles with roles', $stats['profiles_with_roles']],
            ['users matched', $stats['matched']],
            ['staff updated', $stats['staff']],
            ['prospects updated', $stats['prospects']],
            ['skipped (no profile)', $stats['skipped']],
        ]);

        if ($execute) {
            $this->info('Role sync complete.');
        }

        return self::SUCCESS;
    }
}
