<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ListFieldsRequest;
use App\Http\Resources\Api\V1\FieldSchemaResource;
use App\Services\Fields\FieldSchemaService;
use App\Support\Api\ApiResponse;

final class FieldSchemaController extends Controller
{
    public function __construct(
        private readonly FieldSchemaService $schema,
    ) {}

    public function index(ListFieldsRequest $request): \Illuminate\Http\JsonResponse
    {
        $payload = $this->schema->forEntity($request->user(), $request->entity());

        return ApiResponse::resource(new FieldSchemaResource($payload));
    }
}
