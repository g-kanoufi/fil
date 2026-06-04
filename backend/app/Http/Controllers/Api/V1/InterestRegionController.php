<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreInterestRegionRequest;
use App\Http\Requests\Api\V1\UpdateInterestRegionRequest;
use App\Http\Resources\Api\V1\InterestRegionResource;
use App\Models\InterestRegion;
use App\Services\Geography\InterestRegionDefaultsSync;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class InterestRegionController extends Controller
{
    public function __construct(
        private readonly InterestRegionDefaultsSync $defaultsSync,
    ) {}

    public function syncDefaults(): JsonResponse
    {
        $this->authorize('create', InterestRegion::class);

        $counts = $this->defaultsSync->sync();

        return ApiResponse::payload([
            'message' => 'US and Canada state/province defaults synced.',
            'countries' => $counts['countries'],
            'subdivisions' => $counts['subdivisions'],
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', InterestRegion::class);

        $flat = $request->boolean('flat');

        if ($flat) {
            $regions = InterestRegion::query()
                ->with('parent:id,name,code')
                ->where('status', 'active')
                ->whereNotNull('parent_id')
                ->orderBy('parent_id')
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();

            return ApiResponse::collection(InterestRegionResource::collection($regions));
        }

        $regions = InterestRegion::query()
            ->with(['children' => fn ($query) => $query->where('status', 'active')])
            ->whereNull('parent_id')
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return ApiResponse::collection(InterestRegionResource::collection($regions));
    }

    public function store(StoreInterestRegionRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $slug = $validated['slug'] ?? Str::slug((string) $validated['name']);

        $region = InterestRegion::query()->create([
            'parent_id' => $validated['parent_id'] ?? null,
            'name' => $validated['name'],
            'code' => $validated['code'] ?? null,
            'slug' => $slug,
            'sort_order' => $validated['sort_order'] ?? 0,
            'status' => $validated['status'] ?? 'active',
            'legacy_term_id' => $validated['legacy_term_id'] ?? null,
        ]);

        return ApiResponse::resource(
            new InterestRegionResource($region->load('parent')),
            201,
        );
    }

    public function update(UpdateInterestRegionRequest $request, InterestRegion $interestRegion): JsonResponse
    {
        $interestRegion->fill($request->validated());

        if ($request->has('name') && ! $request->has('slug')) {
            $interestRegion->slug = Str::slug((string) $interestRegion->name);
        }

        $interestRegion->save();

        return ApiResponse::resource(
            new InterestRegionResource($interestRegion->refresh()->load(['parent', 'children'])),
        );
    }

    public function destroy(InterestRegion $interestRegion): JsonResponse|Response
    {
        $this->authorize('delete', $interestRegion);

        if ($interestRegion->children()->exists()) {
            return ApiResponse::message(
                'Cannot delete a country or region that still has subdivisions.',
                422,
                ['code' => 'interest_region_has_children'],
            );
        }

        if ($interestRegion->leads()->exists()) {
            return ApiResponse::message(
                'Cannot delete a region that is assigned to leads.',
                422,
                ['code' => 'interest_region_in_use'],
            );
        }

        $interestRegion->delete();

        return response()->noContent();
    }
}
