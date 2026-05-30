<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fdd\ResendFddDelivery;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\FddDeliveryResource;
use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FddDeliveryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Fdd::class);

        $limit = min(max((int) $request->input('limit', 50), 1), 100);

        $deliveries = FddDelivery::query()
            ->with([
                'fdd:id,title,type,area_id',
                'lead:id,title,area_id,lead_fdd_status',
                'recipient:id,name,email',
                'latestSignature',
            ])
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();

        return ApiResponse::collection(FddDeliveryResource::collection($deliveries));
    }

    public function indexForLead(Lead $lead): JsonResponse
    {
        $this->authorize('view', $lead);

        $deliveries = $lead->fddDeliveries()
            ->with(['fdd:id,title,type', 'recipient:id,name,email', 'latestSignature'])
            ->get();

        return ApiResponse::collection(FddDeliveryResource::collection($deliveries));
    }

    public function show(FddDelivery $fddDelivery): JsonResponse
    {
        $this->authorize('view', $fddDelivery->fdd);

        $fddDelivery->load(['fdd', 'lead', 'recipient', 'signatures']);

        return ApiResponse::resource(new FddDeliveryResource($fddDelivery));
    }

    public function resend(FddDelivery $fddDelivery, ResendFddDelivery $resend, Request $request): JsonResponse
    {
        $this->authorize('send', $fddDelivery->fdd);

        $delivery = $resend->handle($fddDelivery, $request->user());

        return ApiResponse::resource(new FddDeliveryResource($delivery));
    }
}
