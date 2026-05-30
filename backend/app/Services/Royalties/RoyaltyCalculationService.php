<?php

declare(strict_types=1);

namespace App\Services\Royalties;

use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Support\Facades\DB;

final class RoyaltyCalculationService
{
    /**
     * @param  array{gross_revenue: float|int|string, order_count?: int, royalty_rate?: float}  $input
     */
    public function calculate(Store $store, array $input): RoyaltyPeriod
    {
        $gross = (float) $input['gross_revenue'];
        $rate = (float) ($input['royalty_rate'] ?? data_get($store->royalty_config, 'default_rate', 0.06));
        $royaltyAmount = round($gross * $rate, 2);
        $now = now();

        return DB::transaction(function () use ($store, $gross, $rate, $royaltyAmount, $now, $input): RoyaltyPeriod {
            $period = RoyaltyPeriod::query()->create([
                'store_id' => $store->id,
                'frequency' => 'weekly',
                'period_start' => $now->copy()->startOfWeek(),
                'period_end' => $now->copy()->endOfWeek(),
                'recorded_at' => $now,
                'gross_revenue' => $gross,
                'order_count' => (int) ($input['order_count'] ?? 0),
                'total_royalties' => $royaltyAmount,
                'status' => 'calculated',
            ]);

            RoyaltyLineItem::query()->create([
                'royalty_period_id' => $period->id,
                'store_id' => $store->id,
                'frequency' => 'weekly',
                'royalty_type' => 'unit',
                'royalty_name' => 'Unit royalty',
                'gross_revenue' => $gross,
                'royalty_rate' => $rate,
                'royalty_amount' => $royaltyAmount,
                'payment_status' => 0,
            ]);

            return $period->load('lineItems');
        });
    }
}
