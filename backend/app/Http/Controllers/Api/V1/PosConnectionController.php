<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\PosConnectionResource;
use App\Jobs\Pos\SyncPosRevenueJob;
use App\Models\PosConnection;
use App\Models\Store;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class PosConnectionController extends Controller
{
    public function indexForStore(Store $store): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('viewAny', PosConnection::class);

        $connections = PosConnection::query()
            ->where('store_id', $store->id)
            ->orderBy('provider')
            ->get();

        return ApiResponse::collection(PosConnectionResource::collection($connections));
    }

    public function sync(Store $store, PosConnection $posConnection): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('sync', PosConnection::class);

        abort_unless($posConnection->store_id === $store->id, 404);

        $posConnection->update([
            'last_synced_at' => now(),
            'status' => 'sync_queued',
        ]);

        SyncPosRevenueJob::dispatch($posConnection->id);

        return ApiResponse::resource(new PosConnectionResource($posConnection->fresh()), 202);
    }
}
