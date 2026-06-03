<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Communications\SendStaffCommunication;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListCommunicationsRequest;
use App\Http\Requests\Api\V1\SendCommunicationRequest;
use App\Http\Resources\Api\V1\CommunicationResource;
use App\Models\Communication;
use App\Models\Lead;
use App\Services\Activity\ActivityRecorder;
use App\Services\Auth\ResourceScopeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class CommunicationController extends Controller
{
    public function index(ListCommunicationsRequest $request, ResourceScopeService $scope): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $query = Communication::query()->orderByDesc('sent_at');

        if ($leadId = $request->leadId()) {
            $lead = Lead::query()->findOrFail($leadId);
            $this->authorize('view', $lead);
            $query->where('lead_id', $leadId);
        } elseif ($scope->isUnrestricted($user)) {
            // Unrestricted staff may list communications without a lead filter.
        } else {
            $visibleLeadIds = Lead::query()
                ->tap(fn ($leadQuery) => $scope->applyLeadScope($leadQuery, $user))
                ->pluck('id');

            if ($visibleLeadIds->isEmpty()) {
                $query->whereRaw('1 = 0');
            } else {
                $query->whereIn('lead_id', $visibleLeadIds);
            }
        }

        return ApiResponse::collection(
            CommunicationResource::collection($query->limit(100)->get()),
        );
    }

    public function store(
        SendCommunicationRequest $request,
        SendStaffCommunication $send,
        ActivityRecorder $activity,
    ): JsonResponse {
        $lead = Lead::query()->findOrFail($request->leadId());
        $this->authorize('view', $lead);

        $communication = $send->handle(
            $lead,
            $request->user(),
            $request->channel(),
            $request->messageBody(),
            $request->subjectLine(),
        );

        $actor = $request->user();
        $channel = strtoupper($request->channel());
        $activity->record(
            category: 'comm',
            action: 'sent',
            summary: sprintf(
                '%s sent %s to lead "%s"',
                $actor?->name ?? 'Staff',
                $channel,
                $lead->title,
            ),
            actor: $actor,
            subject: $lead,
            payload: ['communication_id' => $communication->id, 'channel' => $request->channel()],
        );

        return ApiResponse::resource(new CommunicationResource($communication), 201);
    }
}
