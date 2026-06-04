<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\V1\UpdateProspectApplicationRequest;
use App\Http\Resources\Api\V1\LeadResource;
use App\Services\Portal\ProspectApplicationService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProspectApplicationController extends Controller
{
    public function show(Request $request, ProspectApplicationService $applications): JsonResponse
    {
        return response()->json([
            'data' => $applications->snapshot($request->user()),
        ]);
    }

    public function update(
        UpdateProspectApplicationRequest $request,
        ProspectApplicationService $applications,
    ): JsonResponse {
        $lead = $applications->update($request->user(), $request->fieldValues());

        return ApiResponse::resource(new LeadResource($lead));
    }
}
