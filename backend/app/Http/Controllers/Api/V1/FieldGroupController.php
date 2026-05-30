<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFieldGroupRequest;
use App\Http\Requests\Api\V1\UpdateFieldGroupRequest;
use App\Http\Resources\Api\V1\FieldGroupResource;
use App\Models\FieldGroup;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FieldGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageFields');

        $entity = $request->query('entity');

        $groups = FieldGroup::query()
            ->with(['fields' => function ($query) use ($entity): void {
                if (is_string($entity) && $entity !== '') {
                    $query->where('entity', $entity);
                }

                $query->orderBy('sort_order');
            }])
            ->orderBy('sort_order')
            ->get();

        return ApiResponse::collection(FieldGroupResource::collection($groups));
    }

    public function store(StoreFieldGroupRequest $request): JsonResponse
    {
        $group = FieldGroup::query()->create([
            'key' => $request->validated('key'),
            'title' => $request->validated('title'),
            'slug' => $request->validated('slug'),
            'sort_order' => $request->validated('sort_order', 0),
            'status' => $request->validated('status', 'active'),
        ]);

        return ApiResponse::resource(new FieldGroupResource($group), 201);
    }

    public function update(UpdateFieldGroupRequest $request, FieldGroup $fieldGroup): JsonResponse
    {
        $fieldGroup->fill($request->validated());
        $fieldGroup->save();

        return ApiResponse::resource(new FieldGroupResource($fieldGroup->refresh()));
    }

    public function destroy(FieldGroup $fieldGroup): Response
    {
        $this->authorize('manageFields');

        $fieldGroup->delete();

        return response()->noContent();
    }
}
