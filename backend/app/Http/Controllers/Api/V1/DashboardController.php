<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\DashboardStatsService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DashboardController extends Controller
{
    public function __invoke(Request $request, DashboardStatsService $stats): JsonResponse
    {
        $this->authorize('accessStaffApp');

        $months = max(3, min(12, (int) $request->query('months', 6)));

        return ApiResponse::payload($stats->stats($request->user(), $months));
    }
}
