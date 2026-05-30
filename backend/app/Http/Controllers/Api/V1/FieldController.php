<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fields\CreateField;
use App\Actions\Fields\DeleteField;
use App\Actions\Fields\UpdateField;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFieldRequest;
use App\Http\Requests\Api\V1\UpdateFieldRequest;
use App\Http\Resources\Api\V1\FieldResource;
use App\Models\Field;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class FieldController extends Controller
{
    public function store(StoreFieldRequest $request, CreateField $createField): JsonResponse
    {
        $field = $createField->handle($request->validated());

        return ApiResponse::resource(new FieldResource($field), 201);
    }

    public function update(UpdateFieldRequest $request, Field $field, UpdateField $updateField): JsonResponse
    {
        $field = $updateField->handle($field, $request->validated());

        return ApiResponse::resource(new FieldResource($field));
    }

    public function destroy(Field $field, DeleteField $deleteField): Response
    {
        $this->authorize('manageFields');

        $deleteField->handle($field);

        return response()->noContent();
    }
}
