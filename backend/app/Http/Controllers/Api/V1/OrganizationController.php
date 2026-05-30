<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\OrganizationResource;
use App\Models\Lead;
use App\Models\Organization;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class OrganizationController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Lead::class);

        $organizations = Organization::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(OrganizationResource::collection($organizations));
    }
}
