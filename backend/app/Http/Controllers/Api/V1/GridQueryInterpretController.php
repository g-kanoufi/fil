<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\GridQueryInterpretRequest;
use App\Services\Ai\GridSearchInterpreterService;
use App\Services\Grid\GridQueryService;
use Illuminate\Http\JsonResponse;

final class GridQueryInterpretController extends Controller
{
    public function __construct(
        private readonly GridSearchInterpreterService $interpreter,
        private readonly GridQueryService $gridQuery,
    ) {}

    public function __invoke(GridQueryInterpretRequest $request, string $resource): JsonResponse
    {
        $interpretation = $this->interpreter->interpret(
            $request->user(),
            $resource,
            $request->queryText(),
        );

        $preview = null;

        if ($interpretation['query'] !== []) {
            $preview = $this->gridQuery->query($request->user(), $resource, [
                ...$interpretation['query'],
                'limit' => 1,
                'include_aggregations' => false,
            ]);
        }

        $total = (int) ($preview['hits']['total']['value'] ?? 0);

        return response()->json([
            'data' => [
                ...$interpretation,
                'result_count' => $total,
            ],
        ]);
    }
}
