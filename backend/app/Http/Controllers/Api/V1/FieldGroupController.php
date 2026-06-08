<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreFieldGroupRequest;
use App\Http\Requests\Api\V1\UpdateFieldGroupRequest;
use App\Http\Resources\Api\V1\FieldGroupResource;
use App\Models\FieldGroup;
use App\Services\Activity\ActivityRecorder;
use App\Support\Api\ApiResponse;
use App\Support\Fields\NoteFieldCatalog;
use App\Support\Widget\WidgetFieldCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class FieldGroupController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('manageFields');

        $entity = $request->query('entity');
        $context = $request->query('context');

        $groups = FieldGroup::query()
            ->when(
                $context === 'widget',
                fn ($query) => $query->whereIn('key', WidgetFieldCatalog::allowedGroupKeys()),
            )
            ->when(
                $context !== 'widget',
                fn ($query) => NoteFieldCatalog::applyExcludedGroupScope($query),
            )
            ->with(['fields' => function ($query) use ($entity, $context): void {
                if ($context === 'widget') {
                    WidgetFieldCatalog::applyWidgetFieldScope($query);
                } elseif (is_string($entity) && $entity !== '') {
                    $query->where('entity', $entity);
                }

                NoteFieldCatalog::applyExcludedFieldScope($query);
                $query->where('status', 'active')->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->filter(fn (FieldGroup $group): bool => $context === 'widget' || $group->fields->isNotEmpty())
            ->values();

        return ApiResponse::collection(FieldGroupResource::collection($groups));
    }

    public function store(StoreFieldGroupRequest $request, ActivityRecorder $activity): JsonResponse
    {
        $group = FieldGroup::query()->create([
            'key' => $request->validated('key'),
            'title' => $request->validated('title'),
            'slug' => $request->validated('slug'),
            'sort_order' => $request->validated('sort_order', 0),
            'status' => $request->validated('status', 'active'),
        ]);

        $actor = $request->user();
        $activity->record(
            category: 'settings',
            action: 'created',
            summary: sprintf('%s created field group "%s"', $actor?->name ?? 'Staff', $group->title),
            actor: $actor,
            subject: $group,
        );

        return ApiResponse::resource(new FieldGroupResource($group), 201);
    }

    public function update(
        UpdateFieldGroupRequest $request,
        FieldGroup $fieldGroup,
        ActivityRecorder $activity,
    ): JsonResponse {
        $fieldGroup->fill($request->validated());
        $fieldGroup->save();
        $actor = $request->user();

        $activity->record(
            category: 'settings',
            action: 'updated',
            summary: sprintf('%s updated field group "%s"', $actor?->name ?? 'Staff', $fieldGroup->title),
            actor: $actor,
            subject: $fieldGroup,
            payload: ['changed_keys' => array_keys($request->validated())],
        );

        return ApiResponse::resource(new FieldGroupResource($fieldGroup->refresh()));
    }

    public function destroy(FieldGroup $fieldGroup, ActivityRecorder $activity): Response
    {
        $this->authorize('manageFields');

        $title = $fieldGroup->title;
        $actor = request()->user();
        $fieldGroup->delete();

        $activity->record(
            category: 'settings',
            action: 'deleted',
            summary: sprintf('%s deleted field group "%s"', $actor?->name ?? 'Staff', $title),
            actor: $actor,
        );

        return response()->noContent();
    }
}
