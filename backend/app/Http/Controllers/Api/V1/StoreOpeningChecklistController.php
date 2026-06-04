<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateStoreOpeningChecklistRequest;
use App\Models\Store;
use App\Services\Activity\ActivityRecorder;
use App\Services\Stores\StoreOpeningChecklistService;
use Illuminate\Http\JsonResponse;

final class StoreOpeningChecklistController extends Controller
{
    public function show(Store $store, StoreOpeningChecklistService $checklist): JsonResponse
    {
        $this->authorize('view', $store);

        return response()->json([
            'data' => [
                'items' => $checklist->forStore($store),
                'timeline' => [
                    'buildout_started_at' => $store->buildout_started_at?->toDateString(),
                    'expected_opening_at' => $store->expected_opening_at?->toDateString(),
                    'opened_at' => $store->opened_at?->toDateString(),
                ],
            ],
        ]);
    }

    public function update(
        UpdateStoreOpeningChecklistRequest $request,
        Store $store,
        StoreOpeningChecklistService $checklist,
        ActivityRecorder $activity,
    ): JsonResponse {
        $items = $checklist->updateItems($store, $request->items());

        if ($request->hasTimeline()) {
            $store->fill($request->timelineAttributes());
            $store->save();
        }

        $actor = $request->user();
        $activity->record(
            category: 'store',
            action: 'updated',
            summary: sprintf('%s updated opening checklist for "%s"', $actor?->name ?? 'Staff', $store->name),
            actor: $actor,
            subject: $store,
            payload: ['area' => 'opening_checklist'],
        );

        return response()->json([
            'data' => [
                'items' => $items,
                'timeline' => [
                    'buildout_started_at' => $store->fresh()?->buildout_started_at?->toDateString(),
                    'expected_opening_at' => $store->fresh()?->expected_opening_at?->toDateString(),
                    'opened_at' => $store->fresh()?->opened_at?->toDateString(),
                ],
            ],
        ]);
    }
}
