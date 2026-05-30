<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ClosingResource;
use App\Models\Closing;
use App\Services\Auth\ResourceScopeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class ClosingController extends Controller
{
    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Closing::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Closing::query()->orderByDesc('closing_date')->limit(100);
        $scope->applyClosingScope($query, $user);

        return ApiResponse::collection(ClosingResource::collection($query->get()));
    }

    public function show(Closing $closing): JsonResponse
    {
        $this->authorize('view', $closing);

        return ApiResponse::resource(new ClosingResource($closing));
    }
}
