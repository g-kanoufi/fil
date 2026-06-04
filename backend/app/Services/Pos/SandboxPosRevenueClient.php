<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Contracts\Pos\PosRevenueClient;
use App\Models\PosConnection;
use Carbon\CarbonInterface;

final class SandboxPosRevenueClient implements PosRevenueClient
{
    public function supports(string $provider): bool
    {
        return true;
    }

    public function fetchDailyRevenue(PosConnection $connection, CarbonInterface $date): array
    {
        $meta = is_array($connection->meta ?? null) ? $connection->meta : [];
        $gross = (float) ($meta['sandbox_gross'] ?? 1000);
        $orders = (int) ($meta['sandbox_order_count'] ?? 25);
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        return [
            'gross_sales' => $gross,
            'order_count' => $orders,
            'period_start' => $start,
            'period_end' => $end,
            'raw_payload' => [
                'provider' => $connection->provider,
                'mode' => 'sandbox',
                'snapshot_date' => $date->toDateString(),
            ],
        ];
    }
}
