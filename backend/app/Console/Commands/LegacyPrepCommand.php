<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\InterestRegion;
use App\Models\Lead;
use App\Models\Store;
use App\Services\App\MenuStructureService;
use App\Services\Stores\StoreStatusMenuService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

/**
 * Pre-import readiness checks that do not require a client SQL dump on disk.
 */
final class LegacyPrepCommand extends Command
{
    protected $signature = 'legacy:prep
                            {--strict : Exit 1 on WARN rows (for CI)}';

    protected $description = 'Verify FIL is ready for legacy import (works without client dump; dump path is optional WARN)';

    public function handle(
        MenuStructureService $menus,
        StoreStatusMenuService $storeStatuses,
    ): int {
        $checks = [
            $this->checkDumpPath(),
            $this->checkLegacyAcfPath(),
            $this->checkCoreTables(),
            $this->checkInterestRegionCatalog(),
            $this->checkStoreStatusCatalog($menus, $storeStatuses),
            $this->checkFranchiseAreas(),
            $this->checkDatabaseSamples(),
            $this->runSpotCheck(),
        ];

        $this->table(['Check', 'Status', 'Detail'], $checks);

        $failed = collect($checks)->contains(fn (array $row): bool => $row[1] === 'FAIL');
        $warned = collect($checks)->contains(fn (array $row): bool => $row[1] === 'WARN');

        if ($failed) {
            $this->error('Legacy prep failed — resolve FAIL rows before import.');

            return self::FAILURE;
        }

        if ($warned) {
            $this->warn('Legacy prep passed with warnings.');

            if ($this->option('strict')) {
                return self::FAILURE;
            }

            $this->line('When the client dump is available: set FIL_LEGACY_DUMP_PATH, run `legacy:inventory`, then `legacy:import` (dry-run).');
            $this->line('See docs/LEGACY_IMPORT_DRY_RUN.md');

            return self::SUCCESS;
        }

        $this->info('Legacy prep passed.');

        return self::SUCCESS;
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkDumpPath(): array
    {
        $path = (string) config('fil-legacy.dump_path', env('FIL_LEGACY_DUMP_PATH', ''));

        if ($path === '') {
            return [
                'Legacy dump path',
                'WARN',
                'FIL_LEGACY_DUMP_PATH not set — import blocked until client dump is uploaded',
            ];
        }

        if (! is_readable($path)) {
            return [
                'Legacy dump path',
                'WARN',
                "Not readable: {$path}",
            ];
        }

        return ['Legacy dump path', 'OK', $path];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkLegacyAcfPath(): array
    {
        $path = (string) config('fil-legacy.acf_path', '');

        if ($path !== '' && is_dir($path)) {
            return ['Legacy ACF path', 'OK', $path];
        }

        $bundled = base_path('resources/legacy-acf');

        if (is_dir($bundled)) {
            return ['Legacy ACF path', 'OK', 'bundled resources/legacy-acf'];
        }

        return ['Legacy ACF path', 'FAIL', 'No ACF JSON found — run legacy:import-acf after fixing path'];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkCoreTables(): array
    {
        $leadCount = Lead::query()->count();
        $storeCount = Store::query()->count();

        if ($leadCount === 0 && $storeCount === 0) {
            return [
                'CRM sample data',
                'WARN',
                'No leads/stores — OK for pre-import staging; run import --execute before UI spot-check',
            ];
        }

        return ['CRM sample data', 'OK', "{$leadCount} leads, {$storeCount} stores"];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkInterestRegionCatalog(): array
    {
        $countries = InterestRegion::query()->whereNull('parent_id')->count();
        $subdivisions = InterestRegion::query()->whereNotNull('parent_id')->count();

        if ($countries < 2 || $subdivisions < 50) {
            return [
                'Interest regions (US/CA)',
                'WARN',
                "{$countries} countries, {$subdivisions} subdivisions — run interest-regions:sync-defaults or migrate --seed",
            ];
        }

        return [
            'Interest regions (US/CA)',
            'OK',
            "{$countries} countries, {$subdivisions} states/provinces",
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkStoreStatusCatalog(
        MenuStructureService $menus,
        StoreStatusMenuService $storeStatuses,
    ): array {
        $menu = $storeStatuses->enrichStoreMenus([
            'menuItems' => ['store_status' => ['label' => 'Unit statuses', 'slug' => 'store_status']],
            'subMenuItems' => [],
        ]);

        $statusCount = count($menu['subMenuItems']['store_status'] ?? []);

        if ($statusCount < 4) {
            return [
                'Unit status catalog',
                'FAIL',
                "Expected at least 4 statuses, found {$statusCount}",
            ];
        }

        $storesMenu = $menus->forStaffApp()['menus_with_columns']['stores'] ?? null;
        $areaFilter = isset($storesMenu['menuItems']['store_area']);

        return [
            'Unit status catalog',
            'OK',
            "{$statusCount} statuses in app menus".($areaFilter ? ' (area filter on grid only)' : ''),
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkFranchiseAreas(): array
    {
        $count = Area::query()->count();

        if ($count === 0) {
            return [
                'Franchise areas',
                'WARN',
                'No areas yet — import will create them; optional demo seed',
            ];
        }

        return ['Franchise areas', 'OK', "{$count} area(s)"];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function checkDatabaseSamples(): array
    {
        $path = (string) config('fil-legacy.dump_path', env('FIL_LEGACY_DUMP_PATH', ''));

        if ($path !== '' && is_readable($path)) {
            return [
                'Inventory dry-run',
                'OK',
                'Dump present — run: php artisan legacy:inventory',
            ];
        }

        return [
            'Inventory dry-run',
            'WARN',
            'Skipped — upload dump then run legacy:inventory and legacy:import (dry-run)',
        ];
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private function runSpotCheck(): array
    {
        if (Lead::query()->count() === 0 && Store::query()->count() === 0) {
            return [
                'legacy:spot-check',
                'WARN',
                'Skipped — no leads/stores to sample',
            ];
        }

        $exit = Artisan::call('legacy:spot-check', [
            '--leads' => 3,
            '--stores' => 2,
        ]);

        $this->newLine();
        $this->line(Artisan::output());

        if ($exit !== self::SUCCESS) {
            return ['legacy:spot-check', 'FAIL', 'Command exited with errors'];
        }

        return ['legacy:spot-check', 'OK', 'Tier-1 samples printed above'];
    }
}
