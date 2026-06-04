<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreAreaRequest;
use App\Http\Requests\Api\V1\UpdateAreaRequest;
use App\Http\Resources\Api\V1\AreaResource;
use App\Models\Area;
use App\Models\Store;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class AreaController extends Controller
{
    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Area::class);

        $areas = Area::query()
            ->withCount('stores')
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(AreaResource::collection($areas));
    }

    public function store(StoreAreaRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? Str::slug((string) $validated['name']);

        $area = Area::query()->create([
            'name' => $validated['name'],
            'slug' => $slug,
            'status' => $validated['status'] ?? 'active',
            'approval_status' => $validated['approval_status'] ?? null,
            'territory' => $validated['territory'] ?? null,
        ]);

        return ApiResponse::resource(new AreaResource($area), 201);
    }

    public function show(Area $area): JsonResponse
    {
        $this->authorize('view', $area);

        return ApiResponse::resource(new AreaResource($area));
    }

    public function update(UpdateAreaRequest $request, Area $area): JsonResponse
    {
        $validated = $request->validated();

        if (array_key_exists('name', $validated) && ! array_key_exists('slug', $validated)) {
            $validated['slug'] = Str::slug((string) $validated['name']);
        }

        $area->fill($validated);
        $area->save();

        return ApiResponse::resource(new AreaResource($area->refresh()));
    }

    public function destroy(Area $area): JsonResponse|Response
    {
        $this->authorize('delete', $area);

        if (Store::query()->where('area_id', $area->id)->exists()) {
            return ApiResponse::message(
                'Cannot delete an area that is assigned to stores.',
                422,
                ['code' => 'area_in_use'],
            );
        }

        $area->delete();

        return response()->noContent();
    }
}
