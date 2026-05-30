<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Stores\CreateStore;
use App\Actions\Stores\UpdateStore;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CreateStoreRequest;
use App\Http\Requests\Api\V1\UpdateStoreRequest;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Store;
use App\Services\Activity\ActivityRecorder;
use App\Services\Auth\ResourceScopeService;
use App\Services\Fields\FieldValueWriter;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class StoreController extends Controller
{
    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Store::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Store::query()
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->limit(100);

        $scope->applyStoreScope($query, $user);

        return ApiResponse::collection(StoreResource::collection($query->get()));
    }

    public function store(
        CreateStoreRequest $request,
        CreateStore $createStore,
        FieldValueWriter $fieldWriter,
        ActivityRecorder $activity,
    ): JsonResponse {
        $store = $createStore->handle($request->storeAttributes());

        if ($request->customFieldValues() !== []) {
            $fieldWriter->write('store', $store->id, $request->customFieldValues());
        }

        $store = $store->fresh();
        $actor = $request->user();

        $activity->record(
            category: 'store',
            action: 'created',
            summary: sprintf('%s created store "%s"', $actor?->name ?? 'Staff', $store->name),
            actor: $actor,
            subject: $store,
        );

        return ApiResponse::resource(new StoreResource($store), 201);
    }

    public function show(Store $store): JsonResponse
    {
        $this->authorize('view', $store);

        return ApiResponse::resource(new StoreResource($store));
    }

    public function update(
        UpdateStoreRequest $request,
        Store $store,
        UpdateStore $updateStore,
        FieldValueWriter $fieldWriter,
        ActivityRecorder $activity,
    ): JsonResponse {
        $changedKeys = array_keys($request->storeAttributes());

        if ($request->has('custom')) {
            $changedKeys[] = 'custom';
        }

        $store = $updateStore->handle($store, $request->storeAttributes());

        if ($request->has('custom')) {
            $fieldWriter->write('store', $store->id, $request->customFieldValues());
        }

        $store = $store->fresh();

        if ($changedKeys !== []) {
            $actor = $request->user();
            $activity->record(
                category: 'store',
                action: 'updated',
                summary: sprintf(
                    '%s updated store "%s" (%s)',
                    $actor?->name ?? 'Staff',
                    $store->name,
                    implode(', ', $changedKeys),
                ),
                actor: $actor,
                subject: $store,
                payload: ['changed_keys' => $changedKeys],
            );
        }

        return ApiResponse::resource(new StoreResource($store));
    }
}
