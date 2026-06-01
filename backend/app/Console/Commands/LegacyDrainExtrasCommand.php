<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Services\Legacy\LegacyExtrasDrainService;
use Illuminate\Console\Command;

final class LegacyDrainExtrasCommand extends Command
{
    protected $signature = 'legacy:drain-extras
                            {--entity= : leads, stores, areas, organizations, or all (default all)}
                            {--execute : Persist field_values and trim extras (default dry-run)}';

    protected $description = 'Promote staged extras JSON into field_values where ACF schema matches.';

    public function handle(LegacyExtrasDrainService $drain): int
    {
        $execute = (bool) $this->option('execute');
        $entity = (string) ($this->option('entity') ?: 'all');

        if (! $execute) {
            $this->warn('Dry run — pass --execute to write field_values and trim extras.');
        }

        $targets = match ($entity) {
            'all' => $drain->drainAll($execute),
            'leads' => ['leads' => $drain->drainModel(Lead::class, 'lead', $execute)],
            'stores' => ['stores' => $drain->drainModel(Store::class, 'store', $execute)],
            'areas' => ['areas' => $drain->drainModel(Area::class, 'area', $execute)],
            'organizations' => ['organizations' => $drain->drainModel(Organization::class, 'organization', $execute)],
            default => null,
        };

        if ($targets === null) {
            $this->error('Unknown --entity. Use leads, stores, areas, organizations, or all.');

            return self::FAILURE;
        }

        $rows = [];

        foreach ($targets as $label => $stats) {
            $rows[] = [$label, (string) $stats['entities'], (string) $stats['promoted'], (string) $stats['remaining_keys']];
        }

        $this->table(['Entity', 'Records', 'Keys promoted', 'Keys remaining in extras'], $rows);

        if ($execute) {
            $this->info('Drain complete. Run `php artisan legacy:finalize` to review staged extras.');
        }

        return self::SUCCESS;
    }
}
