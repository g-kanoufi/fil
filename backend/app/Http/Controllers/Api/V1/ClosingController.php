<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\UpdateClosingRequest;
use App\Http\Resources\Api\V1\ClosingResource;
use App\Models\Closing;
use App\Services\Activity\ActivityRecorder;
use App\Services\Auth\ResourceScopeService;
use App\Services\Closings\ClosingDocumentLinker;
use App\Services\Closings\ClosingExportService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class ClosingController extends Controller
{
    public function index(ResourceScopeService $scope): JsonResponse
    {
        $this->authorize('viewAny', Closing::class);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $query = Closing::query()->orderByDesc('closing_date')->limit(100);
        $scope->applyClosingScope($query, $user);

        return ApiResponse::collection(ClosingResource::collection($query->get()));
    }

    public function export(Request $request, ClosingExportService $export): JsonResponse|StreamedResponse
    {
        $this->authorize('viewAny', Closing::class);

        $user = $request->user();
        abort_unless($user !== null, 403);

        $rows = $export->rowsForUser($user);

        if ($request->string('format')->toString() === 'json') {
            return response()->json(['data' => $rows]);
        }

        $filename = 'fil-closings-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'wb');

            if ($handle === false) {
                return;
            }

            fputcsv($handle, [
                'closing_id',
                'title',
                'status',
                'status_label',
                'closing_date',
                'lead',
                'store',
                'area',
                'fee_label',
                'fee_amount_cents',
                'fee_total_cents',
            ]);

            foreach ($rows as $row) {
                fputcsv($handle, [
                    (string) ($row['closing_id'] ?? ''),
                    (string) ($row['title'] ?? ''),
                    (string) ($row['status'] ?? ''),
                    (string) ($row['status_label'] ?? ''),
                    (string) ($row['closing_date'] ?? ''),
                    (string) ($row['lead'] ?? ''),
                    (string) ($row['store'] ?? ''),
                    (string) ($row['area'] ?? ''),
                    (string) ($row['fee_label'] ?? ''),
                    $row['fee_amount_cents'] !== null ? (string) $row['fee_amount_cents'] : '',
                    (string) ($row['fee_total_cents'] ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function show(Closing $closing): JsonResponse
    {
        $this->authorize('view', $closing);

        $closing->load(['lead:id,title', 'store:id,title', 'area:id,name']);

        return ApiResponse::resource(new ClosingResource($closing));
    }

    public function update(
        UpdateClosingRequest $request,
        Closing $closing,
        ActivityRecorder $activity,
        ClosingDocumentLinker $documents,
    ): JsonResponse {
        $changedKeys = [];

        if ($request->has('status')) {
            $closing->status = (string) $request->validated('status');
            $changedKeys[] = 'status';
        }

        if ($request->has('fee_lines')) {
            $extras = is_array($closing->extras) ? $closing->extras : [];
            $extras['fees'] = $request->feeLines();
            $closing->extras = $extras;
            $changedKeys[] = 'fee_lines';
        }

        if ($request->has('document_ids')) {
            $user = $request->user();
            abort_unless($user !== null, 403);
            $documents->sync($closing, $request->documentIds(), $user);
            $changedKeys[] = 'documents';
        }

        if ($changedKeys !== []) {
            $closing->save();

            $actor = $request->user();
            $activity->record(
                category: 'closing',
                action: 'updated',
                summary: sprintf(
                    '%s updated closing "%s" (%s)',
                    $actor?->name ?? 'Staff',
                    $closing->title,
                    implode(', ', $changedKeys),
                ),
                actor: $actor,
                subject: $closing,
                payload: ['changed_keys' => $changedKeys],
            );
        }

        $closing->load(['lead:id,title', 'store:id,title', 'area:id,name']);

        return ApiResponse::resource(new ClosingResource($closing));
    }
}
