<?php

declare(strict_types=1);

namespace App\Contracts\Pos;

use App\Models\PosConnection;
use Carbon\CarbonInterface;

interface PosRevenueClient
{
    public function supports(string $provider): bool;

    /**
     * @return array{
     *     gross_sales: float,
     *     order_count: int,
     *     period_start: CarbonInterface,
     *     period_end: CarbonInterface,
     *     raw_payload: array<string, mixed>
     * }
     */
    public function fetchDailyRevenue(PosConnection $connection, CarbonInterface $date): array;
}
