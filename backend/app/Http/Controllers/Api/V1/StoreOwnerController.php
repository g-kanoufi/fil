<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SyncStoreOwnersRequest;
use App\Http\Resources\Api\V1\StoreOwnerResource;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

final class StoreOwnerController extends Controller
{
    public function index(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        $owners = StoreOwner::query()
            ->with('user:id,name,first_name,last_name,email')
            ->where('store_id', $store->id)
            ->orderByDesc('ownership_pct')
            ->get();

        return ApiResponse::collection(StoreOwnerResource::collection($owners));
    }

    public function sync(SyncStoreOwnersRequest $request, Store $store): JsonResponse
    {
        $this->authorize('update', $store);

        $owners = DB::transaction(function () use ($request, $store) {
            StoreOwner::query()->where('store_id', $store->id)->delete();

            $created = [];

            foreach ($request->owners() as $owner) {
                $created[] = StoreOwner::query()->create([
                    'store_id' => $store->id,
                    'user_id' => $owner['user_id'],
                    'ownership_pct' => $owner['ownership_pct'] ?? null,
                    'role' => $owner['role'] ?? null,
                ]);
            }

            return StoreOwner::query()
                ->with('user:id,name,first_name,last_name,email')
                ->whereIn('id', collect($created)->pluck('id'))
                ->get();
        });

        return ApiResponse::collection(StoreOwnerResource::collection($owners));
    }
}
