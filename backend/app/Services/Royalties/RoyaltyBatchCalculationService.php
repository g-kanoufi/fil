<?php

declare(strict_types=1);

namespace App\Services\Royalties;

use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

final class RoyaltyBatchCalculationService
{
    public function __construct(
        private readonly RoyaltyCalculationService $calculator,
    ) {}

    /**
     * @return array{processed: int, skipped: int, created: int}
     */
    public function calculateDueStores(?Carbon $asOf = null): array
    {
        $asOf ??= now();
        $stats = ['processed' => 0, 'skipped' => 0, 'created' => 0];

        Store::query()
            ->where('status', '!=', 'closed')
            ->orderBy('id')
            ->chunkById(50, function ($stores) use ($asOf, &$stats): void {
                foreach ($stores as $store) {
                    $stats['processed']++;

                    if (! data_get($store->royalty_config, 'auto_calculate', true)) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($this->hasPeriodForWeek($store, $asOf)) {
                        $stats['skipped']++;

                        continue;
                    }

                    $gross = (float) data_get($store->royalty_config, 'scheduled_gross', data_get($store->extras, 'last_pos_gross', 0));

                    if ($gross <= 0) {
                        $stats['skipped']++;

                        continue;
                    }

                    $this->calculator->calculate($store, [
                        'gross_revenue' => $gross,
                        'order_count' => (int) data_get($store->extras, 'last_pos_order_count', 0),
                    ]);

                    $stats['created']++;
                }
            });

        Log::info('Royalty batch calculation finished', $stats);

        return $stats;
    }

    private function hasPeriodForWeek(Store $store, Carbon $asOf): bool
    {
        return RoyaltyPeriod::query()
            ->where('store_id', $store->id)
            ->where('period_start', '<=', $asOf->copy()->endOfWeek())
            ->where('period_end', '>=', $asOf->copy()->startOfWeek())
            ->exists();
    }
}
