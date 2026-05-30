<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GridQueryRequest;
use App\Services\Grid\GridQueryService;
use Illuminate\Http\JsonResponse;

final class GridQueryController extends Controller
{
    public function __construct(
        private readonly GridQueryService $gridQuery,
    ) {}

    public function __invoke(GridQueryRequest $request, string $resource): JsonResponse
    {
        $result = $this->gridQuery->query(
            $request->user(),
            $resource,
            $request->queryPayload(),
        );

        return response()->json($result);
    }
}
