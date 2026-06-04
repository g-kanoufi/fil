<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Contracts\Pos\PosRevenueClient;
use App\Models\PosConnection;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class SquarePosRevenueClient implements PosRevenueClient
{
    public function supports(string $provider): bool
    {
        return $provider === 'square';
    }

    public function isConfiguredFor(PosConnection $connection): bool
    {
        $credentials = is_array($connection->credentials ?? null) ? $connection->credentials : [];

        return filled($credentials['access_token'] ?? null)
            && filled($connection->external_location_id);
    }

    public function fetchDailyRevenue(PosConnection $connection, CarbonInterface $date): array
    {
        if (! $this->isConfiguredFor($connection)) {
            throw new RuntimeException('Square POS connection is missing access_token or location id.');
        }

        /** @var array<string, mixed> $credentials */
        $credentials = $connection->credentials ?? [];
        $accessToken = (string) $credentials['access_token'];
        $baseUrl = rtrim((string) config('services.square.base_url'), '/');
        $start = $date->copy()->startOfDay();
        $end = $date->copy()->endOfDay();

        $response = Http::withToken($accessToken)
            ->acceptJson()
            ->post("{$baseUrl}/v2/orders/search", [
                'location_ids' => [(string) $connection->external_location_id],
                'query' => [
                    'filter' => [
                        'date_time_filter' => [
                            'created_at' => [
                                'start_at' => $start->toIso8601String(),
                                'end_at' => $end->toIso8601String(),
                            ],
                        ],
                    ],
                ],
                'limit' => 500,
            ])
            ->throw();

        /** @var list<array<string, mixed>> $orders */
        $orders = $response->json('orders') ?? [];
        $grossCents = 0;
        $orderCount = 0;

        foreach ($orders as $order) {
            if (($order['state'] ?? null) === 'CANCELED') {
                continue;
            }

            $totalMoney = is_array($order['total_money'] ?? null) ? $order['total_money'] : [];
            $grossCents += (int) ($totalMoney['amount'] ?? 0);
            $orderCount++;
        }

        return [
            'gross_sales' => round($grossCents / 100, 2),
            'order_count' => $orderCount,
            'period_start' => $start,
            'period_end' => $end,
            'raw_payload' => [
                'provider' => 'square',
                'orders_returned' => count($orders),
                'snapshot_date' => $date->toDateString(),
            ],
        ];
    }
}
