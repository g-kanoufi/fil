<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateClosingRequest;
use App\Http\Resources\Api\V1\ClosingResource;
use App\Models\Closing;
use App\Services\Activity\ActivityRecorder;
use App\Services\Auth\ResourceScopeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ClosingController extends Controller
{
    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Closing::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Closing::query()->orderByDesc('closing_date')->limit(100);
        $scope->applyClosingScope($query, $user);

        return ApiResponse::collection(ClosingResource::collection($query->get()));
    }

    public function show(Closing $closing): JsonResponse
    {
        $this->authorize('view', $closing);

        $closing->load(['lead:id,title', 'store:id,title', 'area:id,name']);

        return ApiResponse::resource(new ClosingResource($closing));
    }

    public function update(
        UpdateClosingRequest $request,
        Closing $closing,
        ActivityRecorder $activity,
    ): JsonResponse {
        $changedKeys = [];

        if ($request->has('status')) {
            $closing->status = (string) $request->validated('status');
            $changedKeys[] = 'status';
        }

        if ($request->has('fee_lines')) {
            $extras = is_array($closing->extras) ? $closing->extras : [];
            $extras['fees'] = $request->feeLines();
            $closing->extras = $extras;
            $changedKeys[] = 'fee_lines';
        }

        if ($changedKeys !== []) {
            $closing->save();

            $actor = $request->user();
            $activity->record(
                category: 'closing',
                action: 'updated',
                summary: sprintf(
                    '%s updated closing "%s" (%s)',
                    $actor?->name ?? 'Staff',
                    $closing->title,
                    implode(', ', $changedKeys),
                ),
                actor: $actor,
                subject: $closing,
                payload: ['changed_keys' => $changedKeys],
            );
        }

        $closing->load(['lead:id,title', 'store:id,title', 'area:id,name']);

        return ApiResponse::resource(new ClosingResource($closing));
    }
}
