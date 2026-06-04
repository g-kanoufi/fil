<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\PosConnection;
use App\Models\PosSalesSnapshot;
use App\Models\Store;
use Carbon\Carbon;

final class PosRevenueSyncService
{
    public function __construct(
        private readonly PosRevenueClientResolver $clients,
    ) {}

    public function sync(PosConnection $connection, ?Carbon $date = null): PosSalesSnapshot
    {
        $connection->loadMissing('store');
        $date ??= now()->subDay()->startOfDay();
        $client = $this->clients->forConnection($connection);
        $result = $client->fetchDailyRevenue($connection, $date);

        $snapshot = PosSalesSnapshot::query()->updateOrCreate(
            [
                'pos_connection_id' => $connection->id,
                'snapshot_date' => $date->toDateString(),
            ],
            [
                'store_id' => $connection->store_id,
                'period_start' => $result['period_start'],
                'period_end' => $result['period_end'],
                'gross_sales' => $result['gross_sales'],
                'order_count' => $result['order_count'],
                'raw_payload' => $result['raw_payload'],
                'synced_at' => now(),
            ],
        );

        $store = $connection->store;

        if ($store instanceof Store) {
            $extras = is_array($store->extras ?? null) ? $store->extras : [];
            $extras['last_pos_gross'] = $result['gross_sales'];
            $extras['last_pos_order_count'] = $result['order_count'];
            $extras['last_pos_sync_at'] = now()->toIso8601String();

            $royaltyConfig = is_array($store->royalty_config ?? null) ? $store->royalty_config : [];
            $royaltyConfig['scheduled_gross'] = $result['gross_sales'];

            $store->update([
                'extras' => $extras,
                'royalty_config' => $royaltyConfig,
            ]);
        }

        $connection->update([
            'last_synced_at' => now(),
            'status' => 'synced',
            'meta' => array_merge(is_array($connection->meta ?? null) ? $connection->meta : [], [
                'last_snapshot_id' => $snapshot->id,
                'last_gross_sales' => $result['gross_sales'],
                'last_order_count' => $result['order_count'],
            ]),
        ]);

        return $snapshot;
    }
}
