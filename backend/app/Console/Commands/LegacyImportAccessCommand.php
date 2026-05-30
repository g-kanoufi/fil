<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Console\Command;

final class LegacyImportAccessCommand extends Command
{
    protected $signature = 'legacy:import-access {--execute : Apply seeders (default dry-run)}';

    protected $description = 'Seed FIL roles, permissions, and UI access grants (replaces legacy caps import).';

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        if (! $execute) {
            $this->warn('Dry run — pass --execute to run RolesAndPermissionsSeeder + UiAccessSeeder.');

            return self::SUCCESS;
        }

        $this->call('db:seed', ['--class' => RolesAndPermissionsSeeder::class, '--force' => true]);
        $this->call('db:seed', ['--class' => UiAccessSeeder::class, '--force' => true]);

        $this->info('Access seeders applied.');

        return self::SUCCESS;
    }
}
