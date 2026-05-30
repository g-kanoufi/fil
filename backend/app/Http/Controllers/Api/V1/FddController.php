<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fdd\SendFddDelivery;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\SendFddDeliveryRequest;
use App\Http\Requests\Api\V1\StoreFddRequest;
use App\Http\Requests\Api\V1\UpdateFddRequest;
use App\Http\Resources\Api\V1\FddDeliveryResource;
use App\Http\Resources\Api\V1\FddResource;
use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use App\Services\Fdd\FddDocumentService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FddController extends Controller
{
    public function summary(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Fdd::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $fddQuery = Fdd::query()->where('status', 'active');
        $scope->applyFddScope($fddQuery, $user);

        $visibleFddIds = (clone $fddQuery)->pluck('id');

        return ApiResponse::payload([
            'fdds' => [
                'total' => (clone $fddQuery)->count(),
                'unit' => (clone $fddQuery)->where('type', 'unit')->count(),
                'area' => (clone $fddQuery)->where('type', 'area')->count(),
            ],
            'deliveries' => [
                'total' => FddDelivery::query()->whereIn('fdd_id', $visibleFddIds)->count(),
                'sent_30d' => FddDelivery::query()
                    ->whereIn('fdd_id', $visibleFddIds)
                    ->where('sent_at', '>=', now()->subDays(30))
                    ->count(),
                'pending_signature' => FddDelivery::query()
                    ->whereIn('fdd_id', $visibleFddIds)
                    ->where('status', 'sent')
                    ->whereDoesntHave('signatures', fn ($q) => $q->where('status', 'signed'))
                    ->count(),
            ],
        ]);
    }

    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Fdd::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Fdd::query()
            ->with(['area:id,name', 'document:id,title'])
            ->withCount('deliveries')
            ->where('status', 'active')
            ->orderBy('type')
            ->orderBy('title');

        $scope->applyFddScope($query, $user);

        return ApiResponse::collection(FddResource::collection($query->get()));
    }

    public function show(Fdd $fdd): JsonResponse
    {
        $this->authorize('view', $fdd);

        $fdd->load(['area:id,name', 'document:id,title']);

        return ApiResponse::resource(new FddResource($fdd));
    }

    public function store(StoreFddRequest $request, FddDocumentService $documents): JsonResponse
    {
        $validated = $request->validated();
        unset($validated['pdf']);

        $fdd = Fdd::query()->create([
            ...$validated,
            'status' => $validated['status'] ?? 'active',
        ]);

        $document = $documents->storeFromUpload($request->file('pdf'), $fdd, $request->user());
        $fdd->update(['document_id' => $document->id]);

        $fdd->load(['area:id,name', 'document:id,title'])->loadCount('deliveries');

        return ApiResponse::resource(new FddResource($fdd), 201);
    }

    public function update(
        UpdateFddRequest $request,
        Fdd $fdd,
        FddDocumentService $documents,
    ): JsonResponse {
        $validated = $request->validated();
        unset($validated['pdf']);

        if ($validated !== []) {
            $fdd->update($validated);
        }

        if ($request->hasFile('pdf')) {
            $documents->replaceForFdd($request->file('pdf'), $fdd, $request->user());
        }

        $fdd->load(['area:id,name', 'document:id,title'])->loadCount('deliveries');

        return ApiResponse::resource(new FddResource($fdd));
    }

    public function sendToLead(
        SendFddDeliveryRequest $request,
        Fdd $fdd,
        Lead $lead,
        SendFddDelivery $sendFdd,
    ): JsonResponse {
        $this->authorize('view', $lead);

        $recipient = null;

        if ($request->filled('recipient_user_id')) {
            $recipient = User::query()->findOrFail((int) $request->input('recipient_user_id'));
        }

        $delivery = $sendFdd->handle($fdd, $lead, $request->user(), $recipient);

        return ApiResponse::resource(new FddDeliveryResource($delivery), 201);
    }
}
