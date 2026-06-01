<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\Closing;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

final class LegacyImportFinalizeCommand extends Command
{
    protected $signature = 'legacy:finalize {--strict : Fail if any entity still has non-empty extras}';

    protected $description = 'Verify FIL data is fully migrated (no runtime legacy dependencies).';

    public function handle(): int
    {
        $strict = (bool) $this->option('strict');

        $extrasCounts = [
            'leads' => $this->countExtras(Lead::class),
            'stores' => $this->countExtras(Store::class),
            'areas' => $this->countExtras(Area::class),
            'organizations' => $this->countExtras(Organization::class),
            'fdds' => $this->countExtras(Fdd::class),
            'closings' => $this->countExtras(Closing::class),
        ];

        $rows = [
            ['Check', 'Result'],
            ['Leads with legacy_post_id', (string) Lead::query()->whereNotNull('legacy_post_id')->count()],
            ['Stores with legacy_post_id', (string) Store::query()->whereNotNull('legacy_post_id')->count()],
            ['FDDs imported', (string) Fdd::query()->count()],
            ['Organizations imported', (string) Organization::query()->count()],
            ['Closings imported', (string) Closing::query()->count()],
        ];

        foreach ($extrasCounts as $entity => $count) {
            $rows[] = ["{$entity} with extras (import-only staging)", (string) $count];
        }

        $this->table($rows[0], array_slice($rows, 1));

        $totalExtras = array_sum($extrasCounts);

        if ($totalExtras === 0) {
            $this->info('Migration complete — no staged extras remain. Runtime code should not query extras or legacy_* columns.');

            return self::SUCCESS;
        }

        $this->warn("{$totalExtras} rows still have extras JSON. Run `legacy:drain-extras --execute` or promote keys to columns/field_values.");

        if ($strict) {
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * @param  class-string<Model>  $model
     */
    private function countExtras(string $model): int
    {
        $instance = new $model;

        if (! Schema::hasColumn($instance->getTable(), 'extras')) {
            return 0;
        }

        return (int) $model::query()
            ->whereNotNull('extras')
            ->when(
                Schema::getConnection()->getDriverName() === 'pgsql',
                fn ($query) => $query->whereRaw("extras::text NOT IN ('[]', '{}')"),
                fn ($query) => $query
                    ->where('extras', '!=', '[]')
                    ->where('extras', '!=', '{}'),
            )
            ->count();
    }
}
