<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreDripCampaignRequest;
use App\Http\Requests\Api\V1\UpdateDripCampaignRequest;
use App\Http\Resources\Api\V1\DripCampaignResource;
use App\Models\DripCampaign;
use App\Services\Drips\DripCampaignStepSyncService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class DripCampaignController extends Controller
{
    public function schema(): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        return ApiResponse::payload([
            'trigger_events' => [
                ['value' => 'lead_created', 'label' => 'Lead created (widget intake)'],
                ['value' => 'pipeline_midnight', 'label' => 'Pipeline midnight job'],
            ],
            'statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'paused', 'label' => 'Paused'],
                ['value' => 'draft', 'label' => 'Draft'],
            ],
            'channels' => [
                ['value' => 'email', 'label' => 'Email'],
                ['value' => 'sms', 'label' => 'SMS'],
            ],
            'step_statuses' => [
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ]);
    }

    public function index(): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        $campaigns = DripCampaign::query()
            ->withCount(['steps', 'enrollments'])
            ->with(['steps' => fn ($query) => $query->orderBy('sort_order')])
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(DripCampaignResource::collection($campaigns));
    }

    public function show(DripCampaign $dripCampaign): JsonResponse
    {
        $this->authorize('manage', Settings::class);

        $dripCampaign->load(['steps' => fn ($query) => $query->orderBy('sort_order')])
            ->loadCount(['steps', 'enrollments']);

        return ApiResponse::resource(new DripCampaignResource($dripCampaign));
    }

    public function store(
        StoreDripCampaignRequest $request,
        DripCampaignStepSyncService $stepSync,
    ): JsonResponse {
        $validated = $request->validated();
        $steps = $validated['steps'] ?? null;
        unset($validated['steps']);

        $campaign = DripCampaign::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'draft',
        ]);

        if (is_array($steps)) {
            $stepSync->sync($campaign, $steps);
        }

        $campaign->refresh()->load(['steps' => fn ($query) => $query->orderBy('sort_order')])
            ->loadCount(['steps', 'enrollments']);

        return ApiResponse::resource(new DripCampaignResource($campaign), 201);
    }

    public function update(
        UpdateDripCampaignRequest $request,
        DripCampaign $dripCampaign,
        DripCampaignStepSyncService $stepSync,
    ): JsonResponse {
        $validated = $request->validated();
        $steps = $validated['steps'] ?? null;
        unset($validated['steps']);

        if ($validated !== []) {
            $dripCampaign->update($validated);
        }

        if (is_array($steps)) {
            $stepSync->sync($dripCampaign, $steps);
        }

        $dripCampaign->refresh()->load(['steps' => fn ($query) => $query->orderBy('sort_order')])
            ->loadCount(['steps', 'enrollments']);

        return ApiResponse::resource(new DripCampaignResource($dripCampaign));
    }

    public function destroy(DripCampaign $dripCampaign): Response
    {
        $this->authorize('manage', Settings::class);

        if ($dripCampaign->enrollments()->exists()) {
            $dripCampaign->update(['status' => 'paused']);
        } else {
            $dripCampaign->delete();
        }

        return response()->noContent();
    }
}
