<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Area;
use App\Support\Api\ApiResponse;
use App\Support\Geography\InterestRegionCatalog;
use Illuminate\Http\JsonResponse;

final class GeographyController extends Controller
{
    public function northAmerica(): JsonResponse
    {
        $this->authorize('viewAny', Area::class);

        return ApiResponse::payload([
            'countries' => InterestRegionCatalog::countries(),
        ]);
    }
}
