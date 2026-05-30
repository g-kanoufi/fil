<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Jobs\Documents\ProcessDocumentExportJob;
use App\Models\Document;
use App\Services\Documents\DocumentExportRepository;
use App\Services\Documents\DocumentExportService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentsExportController extends Controller
{
    public function start(Request $request, DocumentExportService $exports): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $entity = (string) $request->input('entity', '');
        $docType = (string) $request->input('doc_type', '');
        $docTypeLabel = (string) $request->input('doc_type_label', $docType);

        try {
            $result = $exports->start((int) $request->user()->id, $entity, $docType, $docTypeLabel);
        } catch (\InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        ProcessDocumentExportJob::dispatch($result['export_id']);

        return ApiResponse::payload($result);
    }

    public function status(Request $request, DocumentExportRepository $exports): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $exportId = (string) $request->query('export_id', '');

        if ($exportId === '') {
            return response()->json(['message' => 'Missing export_id.'], 422);
        }

        $state = $exports->get($exportId);

        if ($state === null) {
            return response()->json(['message' => 'Export not found.'], 404);
        }

        if ((int) ($state['user_id'] ?? 0) !== (int) $request->user()->id) {
            return response()->json(['message' => 'Export not found.'], 404);
        }

        $zipUrl = null;

        if (($state['status'] ?? '') === 'completed' && filled($state['zip_path'] ?? null)) {
            $zipUrl = route('document-exports.download', ['exportId' => $exportId], false);
        }

        return ApiResponse::payload([
            'export_id' => $state['export_id'],
            'status' => $state['status'],
            'message' => $state['message'] ?? null,
            'progress' => $state['progress'] ?? null,
            'zip_url' => $zipUrl,
            'error' => $state['error'] ?? null,
        ]);
    }
}
