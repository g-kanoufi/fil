<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fields\ReorderFields;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReorderFieldsRequest;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class FieldReorderController extends Controller
{
    public function __invoke(ReorderFieldsRequest $request, ReorderFields $reorder): JsonResponse
    {
        $reorder->handle($request->items());

        return ApiResponse::message('Fields reordered.');
    }
}
