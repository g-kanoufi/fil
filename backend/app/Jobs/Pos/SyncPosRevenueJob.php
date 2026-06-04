<?php

declare(strict_types=1);

namespace App\Jobs\Pos;

use App\Models\PosConnection;
use App\Services\Pos\PosRevenueSyncService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class SyncPosRevenueJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $posConnectionId,
    ) {}

    public function handle(PosRevenueSyncService $sync): void
    {
        $connection = PosConnection::query()->find($this->posConnectionId);

        if ($connection === null) {
            return;
        }

        $snapshot = $sync->sync($connection);

        Log::info('POS sync completed', [
            'pos_connection_id' => $connection->id,
            'provider' => $connection->provider,
            'snapshot_id' => $snapshot->id,
            'gross_sales' => $snapshot->gross_sales,
        ]);
    }
}
