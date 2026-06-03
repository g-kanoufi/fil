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
use App\Services\Activity\ActivityRecorder;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

final class FieldController extends Controller
{
    public function store(
        StoreFieldRequest $request,
        CreateField $createField,
        ActivityRecorder $activity,
    ): JsonResponse {
        $field = $createField->handle($request->validated());
        $actor = $request->user();

        $activity->record(
            category: 'settings',
            action: 'created',
            summary: sprintf('%s created field "%s"', $actor?->name ?? 'Staff', $field->label),
            actor: $actor,
            subject: $field,
        );

        return ApiResponse::resource(new FieldResource($field), 201);
    }

    public function update(
        UpdateFieldRequest $request,
        Field $field,
        UpdateField $updateField,
        ActivityRecorder $activity,
    ): JsonResponse {
        $field = $updateField->handle($field, $request->validated());
        $actor = $request->user();

        $activity->record(
            category: 'settings',
            action: 'updated',
            summary: sprintf('%s updated field "%s"', $actor?->name ?? 'Staff', $field->label),
            actor: $actor,
            subject: $field,
            payload: ['changed_keys' => array_keys($request->validated())],
        );

        return ApiResponse::resource(new FieldResource($field));
    }

    public function destroy(Field $field, DeleteField $deleteField, ActivityRecorder $activity): Response
    {
        $this->authorize('manageFields');

        $label = $field->label;
        $actor = request()->user();

        $deleteField->handle($field);

        $activity->record(
            category: 'settings',
            action: 'deleted',
            summary: sprintf('%s deleted field "%s"', $actor?->name ?? 'Staff', $label),
            actor: $actor,
        );

        return response()->noContent();
    }
}
