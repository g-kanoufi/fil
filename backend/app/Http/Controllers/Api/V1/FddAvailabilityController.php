<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Services\Fdd\FddAreaAvailabilityService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class FddAvailabilityController extends Controller
{
    public function forArea(Area $area, FddAreaAvailabilityService $availability): JsonResponse
    {
        $this->authorize('viewAny', Fdd::class);

        return ApiResponse::payload($availability->forArea($area->id));
    }

    public function forLead(Lead $lead, FddAreaAvailabilityService $availability): JsonResponse
    {
        $this->authorize('view', $lead);

        return ApiResponse::payload($availability->forLead($lead));
    }
}
