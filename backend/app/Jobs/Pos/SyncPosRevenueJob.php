<?php

declare(strict_types=1);

namespace App\Jobs\Pos;

use App\Models\PosConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

final class SyncPosRevenueJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $posConnectionId,
    ) {}

    public function handle(): void
    {
        $connection = PosConnection::query()->find($this->posConnectionId);

        if ($connection === null) {
            return;
        }

        $connection->update([
            'last_synced_at' => now(),
            'status' => 'synced',
            'meta' => array_merge($connection->meta ?? [], [
                'last_sync_job' => now()->toIso8601String(),
                'note' => 'POS adapter stub — wire Square/Clover/Booker in production',
            ]),
        ]);

        Log::info('POS sync completed (stub)', [
            'pos_connection_id' => $connection->id,
            'provider' => $connection->provider,
        ]);
    }
}
