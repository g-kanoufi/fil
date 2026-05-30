<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AreaResource;
use App\Models\Area;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AreaController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', \App\Models\Store::class);

        $areas = Area::query()
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(AreaResource::collection($areas));
    }
}
