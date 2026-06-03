<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Royalties\TriggerAchTransfer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CalculateRoyaltyRequest;
use App\Http\Requests\Api\V1\TriggerAchTransferRequest;
use App\Http\Resources\Api\V1\AchTransferResource;
use App\Http\Resources\Api\V1\RoyaltyPeriodResource;
use App\Models\AchTransfer;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Services\Activity\ActivityRecorder;
use App\Services\Royalties\RoyaltyCalculationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class RoyaltyController extends Controller
{
    public function indexForStore(Store $store): JsonResponse
    {
        $this->authorize('view', $store);
        $this->authorize('viewAny', RoyaltyPeriod::class);

        $periods = RoyaltyPeriod::query()
            ->where('store_id', $store->id)
            ->with('lineItems')
            ->orderByDesc('period_start')
            ->limit(50)
            ->get();

        return ApiResponse::collection(RoyaltyPeriodResource::collection($periods));
    }

    public function show(RoyaltyPeriod $royaltyPeriod): JsonResponse
    {
        $this->authorize('view', $royaltyPeriod);

        $royaltyPeriod->load('lineItems');

        return ApiResponse::resource(new RoyaltyPeriodResource($royaltyPeriod));
    }

    public function calculate(
        CalculateRoyaltyRequest $request,
        Store $store,
        RoyaltyCalculationService $calculator,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('view', $store);
        $this->authorize('calculate', RoyaltyPeriod::class);

        $period = $calculator->calculate($store, $request->validated());
        $actor = $request->user();

        $activity->record(
            category: 'finance',
            action: 'calculated',
            summary: sprintf(
                '%s calculated royalties for store "%s"',
                $actor?->name ?? 'Staff',
                $store->name,
            ),
            actor: $actor,
            subject: $store,
            payload: ['royalty_period_id' => $period->id],
        );

        return ApiResponse::resource(new RoyaltyPeriodResource($period), 201);
    }

    public function triggerAch(
        TriggerAchTransferRequest $request,
        Store $store,
        RoyaltyPeriod $royaltyPeriod,
        TriggerAchTransfer $triggerAch,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('view', $store);
        $this->authorize('manage', AchTransfer::class);

        abort_unless($royaltyPeriod->store_id === $store->id, 404);

        $amount = $request->amount() ?? (float) $royaltyPeriod->total_royalties;

        $transfer = $triggerAch->handle($store, $royaltyPeriod, $amount);
        $actor = $request->user();

        $activity->record(
            category: 'finance',
            action: 'triggered',
            summary: sprintf(
                '%s triggered ACH for store "%s"',
                $actor?->name ?? 'Staff',
                $store->name,
            ),
            actor: $actor,
            subject: $store,
            payload: ['ach_transfer_id' => $transfer->id, 'royalty_period_id' => $royaltyPeriod->id],
        );

        return ApiResponse::resource(new AchTransferResource($transfer), 201);
    }
}
