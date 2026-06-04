<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AchTransferResource;
use App\Models\AchTransfer;
use App\Services\Ach\AchReconciliationService;
use App\Services\Auth\ResourceScopeService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AchTransferController extends Controller
{
    public function index(Request $request, ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', AchTransfer::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $query = AchTransfer::query()
            ->with('store:id,name')
            ->orderByDesc('transferred_at')
            ->orderByDesc('id');

        $scope->applyAchTransferScope($query, $user);

        if ($storeId = $request->integer('store_id')) {
            $query->where('store_id', $storeId);
        }

        if ($status = $request->query('status')) {
            if (is_numeric($status)) {
                $query->where('status', (int) $status);
            } elseif (is_string($status) && $status !== '') {
                $query->where('provider_status', $status);
            }
        }

        $transfers = $query->limit(200)->get();

        return ApiResponse::collection(AchTransferResource::collection($transfers));
    }

    public function reconciliation(Request $request, AchReconciliationService $reconciliation): JsonResponse
    {
        $this->authorize('viewAny', AchTransfer::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        return ApiResponse::payload($reconciliation->summary($user));
    }

    public function show(AchTransfer $achTransfer): JsonResponse
    {
        $this->authorize('view', $achTransfer);

        $achTransfer->load('store:id,name');

        return ApiResponse::resource(new AchTransferResource($achTransfer));
    }
}
