<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Leads\ConvertLeadToStore;
use App\Actions\Leads\CreateLead;
use App\Actions\Leads\UpdateLead;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StaffCreateLeadRequest;
use App\Http\Requests\Api\V1\TransitionLeadPhaseRequest;
use App\Http\Requests\Api\V1\UpdateLeadRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Http\Resources\Api\V1\StoreResource;
use App\Models\Lead;
use App\Services\Activity\ActivityRecorder;
use App\Services\Auth\ResourceScopeService;
use App\Services\Fields\FieldValueWriter;
use App\Services\Leads\LeadPipelineService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class LeadController extends Controller
{
    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Lead::query()
            ->with('owner:id,first_name,last_name,name')
            ->where('status', 'active')
            ->orderByDesc('updated_at')
            ->limit(100);

        $scope->applyLeadScope($query, $user);

        return ApiResponse::collection(LeadResource::collection($query->get()));
    }

    public function store(
        StaffCreateLeadRequest $request,
        CreateLead $createLead,
        FieldValueWriter $fieldWriter,
        ActivityRecorder $activity,
    ): JsonResponse {
        $lead = $createLead->handle($request->leadAttributes(), $request->user());

        if ($request->customFieldValues() !== []) {
            $fieldWriter->write('lead', $lead->id, $request->customFieldValues());
        }

        $lead = $lead->fresh(['owner', 'prospect', 'phaseEvents']);

        $actor = $request->user();
        $activity->record(
            category: 'lead',
            action: 'created',
            summary: sprintf('%s created lead "%s"', $actor?->name ?? 'Staff', $lead->title),
            actor: $actor,
            subject: $lead,
        );

        return ApiResponse::resource(new LeadResource($lead), 201);
    }

    public function show(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $lead->load(['owner', 'prospect', 'phaseEvents']);

        return ApiResponse::resource(new LeadResource($lead));
    }

    public function update(
        UpdateLeadRequest $request,
        Lead $lead,
        UpdateLead $updateLead,
        FieldValueWriter $fieldWriter,
        ActivityRecorder $activity,
    ): JsonResponse {
        $changedKeys = array_keys($request->leadAttributes());

        if ($request->has('custom')) {
            $changedKeys[] = 'custom';
        }

        $lead = $updateLead->handle($lead, $request->leadAttributes());

        if ($request->has('custom')) {
            $fieldWriter->write('lead', $lead->id, $request->customFieldValues());
        }

        $lead = $lead->fresh(['owner', 'prospect', 'phaseEvents']);

        if ($changedKeys !== []) {
            $actor = $request->user();
            $activity->record(
                category: 'lead',
                action: 'updated',
                summary: sprintf(
                    '%s updated lead "%s" (%s)',
                    $actor?->name ?? 'Staff',
                    $lead->title,
                    implode(', ', $changedKeys),
                ),
                actor: $actor,
                subject: $lead,
                payload: ['changed_keys' => $changedKeys],
            );
        }

        return ApiResponse::resource(new LeadResource($lead));
    }

    public function transitionPhase(
        TransitionLeadPhaseRequest $request,
        Lead $lead,
        LeadPipelineService $pipeline,
    ): JsonResponse {
        $lead = $pipeline->transition(
            $lead,
            $request->toPhase(),
            $request->user(),
            $request->meta(),
        );

        return ApiResponse::resource(new LeadResource($lead));
    }

    public function convert(
        Lead $lead,
        ConvertLeadToStore $convertLeadToStore,
        ActivityRecorder $activity,
    ): JsonResponse {
        $this->authorize('update', $lead);

        $store = $convertLeadToStore->handle($lead, request()->user());
        $actor = request()->user();

        $activity->record(
            category: 'lead',
            action: 'converted',
            summary: sprintf(
                '%s converted lead "%s" to store "%s"',
                $actor?->name ?? 'Staff',
                $lead->title,
                $store->name,
            ),
            actor: $actor,
            subject: $lead,
            object: $store,
        );

        return ApiResponse::resource(new StoreResource($store), 201);
    }
}
