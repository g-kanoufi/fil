<?php

declare(strict_types=1);

namespace App\Services\Royalties;

use App\Models\Area;
use App\Models\AreaRoyalty;
use App\Models\RoyaltyLineItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class AreaRoyaltyCalculationService
{
    /**
     * @return array{processed: int, skipped: int, created: int}
     */
    public function saveDueAreaRoyalties(?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $calcDay = (int) config('fil-royalties.area_royalties_calc_day', 1);

        if ($asOf->day !== $calcDay) {
            return ['processed' => 0, 'skipped' => 0, 'created' => 0];
        }

        $period = $asOf->copy()->startOfMonth()->subMonth();
        $stats = ['processed' => 0, 'skipped' => 0, 'created' => 0];

        Area::query()->orderBy('id')->chunkById(25, function ($areas) use ($period, &$stats): void {
            foreach ($areas as $area) {
                $stats['processed']++;

                if (AreaRoyalty::query()->where('area_id', $area->id)->whereDate('period', $period)->exists()) {
                    $stats['skipped']++;

                    continue;
                }

                $storeIds = Store::query()->where('area_id', $area->id)->pluck('id');

                if ($storeIds->isEmpty()) {
                    $stats['skipped']++;

                    continue;
                }

                $lineItems = RoyaltyLineItem::query()
                    ->whereIn('store_id', $storeIds)
                    ->where('royalty_type', 'unit')
                    ->whereHas('period', function ($query) use ($period): void {
                        $query
                            ->whereDate('period_start', '>=', $period->copy()->startOfMonth())
                            ->whereDate('period_end', '<=', $period->copy()->endOfMonth());
                    })
                    ->get();

                if ($lineItems->isEmpty()) {
                    $stats['skipped']++;

                    continue;
                }

                $sumUnit = (float) $lineItems->sum('royalty_amount');
                $sumSales = (float) $lineItems->sum('gross_revenue');
                $percentage = (float) data_get($area->extras, 'area_royalty_percentage', config('fil-royalties.default_area_royalty_percentage', 0.5));
                $amount = round($sumUnit * ($percentage / 100), 2);

                AreaRoyalty::query()->create([
                    'area_id' => $area->id,
                    'period' => $period->toDateString(),
                    'frequency' => 'monthly',
                    'recorded_at' => now(),
                    'store_ids' => $storeIds->values()->all(),
                    'royalty_line_item_ids' => $lineItems->pluck('id')->values()->all(),
                    'sum_total_sales' => $sumSales,
                    'sum_unit_royalties' => $sumUnit,
                    'sum_area_royalties' => $amount,
                    'sum_ach_available' => $amount,
                    'percentage' => $percentage,
                    'amount' => $amount,
                    'payment_status' => 0,
                ]);

                $stats['created']++;
            }
        });

        Log::info('Area royalty batch finished', $stats);

        return $stats;
    }
}
