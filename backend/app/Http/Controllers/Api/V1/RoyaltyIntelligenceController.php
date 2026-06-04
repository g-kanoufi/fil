<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyPeriod;
use App\Services\Royalties\RoyaltyIntelligenceService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class RoyaltyIntelligenceController extends Controller
{
    public function __invoke(Request $request, RoyaltyIntelligenceService $intelligence): JsonResponse
    {
        $this->authorize('viewAny', RoyaltyPeriod::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $days = max(1, min(365, $request->integer('days', 30)));

        return ApiResponse::payload($intelligence->summary($user, $days));
    }
}
