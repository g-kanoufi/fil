<?php

declare(strict_types=1);

namespace App\Services\Royalties;

use App\Models\Area;
use App\Models\AreaRoyalty;
use App\Models\RoyaltyLineItem;
use App\Models\Store;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class RoyaltyIntelligenceService
{
    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user, int $days = 30): array
    {
        $since = now()->subDays(max(1, $days))->startOfDay();

        $lineQuery = RoyaltyLineItem::query()
            ->where('created_at', '>=', $since)
            ->where('royalty_type', 'unit');

        $this->applyLineItemScope($lineQuery, $user);

        $unitTotal = (clone $lineQuery)->sum('royalty_amount');
        $grossTotal = (clone $lineQuery)->sum('gross_revenue');

        /** @var list<array{store_id: int, store_name: string, area_id: int|null, gross_revenue: string, royalty_amount: string}> $byStore */
        $byStore = (clone $lineQuery)
            ->select([
                'store_id',
                DB::raw('SUM(gross_revenue) as gross_revenue'),
                DB::raw('SUM(royalty_amount) as royalty_amount'),
            ])
            ->groupBy('store_id')
            ->orderByDesc('royalty_amount')
            ->limit(12)
            ->get()
            ->map(function (RoyaltyLineItem $row): array {
                $store = Store::query()->find($row->store_id);

                return [
                    'store_id' => (int) $row->store_id,
                    'store_name' => $store?->name ?? 'Store #'.$row->store_id,
                    'area_id' => $store?->area_id,
                    'gross_revenue' => (string) $row->gross_revenue,
                    'royalty_amount' => (string) $row->royalty_amount,
                ];
            })
            ->all();

        $areaQuery = AreaRoyalty::query()->where('recorded_at', '>=', $since);
        $this->applyAreaRoyaltyScope($areaQuery, $user);

        /** @var list<array{area_id: int, area_name: string, amount: string, sum_unit_royalties: string}> $byArea */
        $byArea = (clone $areaQuery)
            ->select([
                'area_id',
                DB::raw('SUM(amount) as amount'),
                DB::raw('SUM(sum_unit_royalties) as sum_unit_royalties'),
            ])
            ->groupBy('area_id')
            ->orderByDesc('amount')
            ->limit(12)
            ->get()
            ->map(function (AreaRoyalty $row): array {
                $area = Area::query()->find($row->area_id);

                return [
                    'area_id' => (int) $row->area_id,
                    'area_name' => $area?->name ?? 'Area #'.$row->area_id,
                    'amount' => (string) $row->amount,
                    'sum_unit_royalties' => (string) $row->sum_unit_royalties,
                ];
            })
            ->all();

        return [
            'period_days' => $days,
            'unit' => [
                'store_count' => count($byStore),
                'total_gross' => (string) ($grossTotal ?: '0.00'),
                'total_royalties' => (string) ($unitTotal ?: '0.00'),
                'by_store' => $byStore,
            ],
            'area' => [
                'area_count' => count($byArea),
                'total_royalties' => (string) ((clone $areaQuery)->sum('amount') ?: '0.00'),
                'by_area' => $byArea,
            ],
        ];
    }

    /**
     * @param  Builder<RoyaltyLineItem>  $query
     */
    private function applyLineItemScope(Builder $query, User $user): void
    {
        if ($this->scope->isUnrestricted($user)) {
            return;
        }

        $storeIds = Store::query()
            ->tap(fn (Builder $storeQuery) => $this->scope->applyStoreScope($storeQuery, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($storeIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('store_id', $storeIds);
    }

    /**
     * @param  Builder<AreaRoyalty>  $query
     */
    private function applyAreaRoyaltyScope(Builder $query, User $user): void
    {
        if ($this->scope->isUnrestricted($user)) {
            return;
        }

        if ($this->scope->tier($user) === 'area') {
            $areaIds = $this->scope->assignedAreaIds($user);

            if ($areaIds === []) {
                $query->whereRaw('1 = 0');

                return;
            }

            $query->whereIn('area_id', $areaIds);

            return;
        }

        $storeIds = Store::query()
            ->tap(fn (Builder $storeQuery) => $this->scope->applyStoreScope($storeQuery, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($storeIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $areaIds = Store::query()->whereIn('id', $storeIds)->pluck('area_id')->filter()->unique()->values()->all();
        $query->whereIn('area_id', $areaIds !== [] ? $areaIds : [-1]);
    }
}
