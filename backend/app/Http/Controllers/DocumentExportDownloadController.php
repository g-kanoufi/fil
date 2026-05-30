<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\Documents\DocumentExportRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

final class DocumentExportDownloadController extends Controller
{
    public function __invoke(string $exportId, DocumentExportRepository $exports): Response
    {
        $this->authorize('viewAny', Document::class);

        $state = $exports->get($exportId);

        if ($state === null || (int) ($state['user_id'] ?? 0) !== (int) auth()->id()) {
            abort(404);
        }

        if (($state['status'] ?? '') !== 'completed') {
            abort(409, 'Export is not ready.');
        }

        $zipPath = (string) ($state['zip_path'] ?? '');

        if ($zipPath === '' || ! Storage::disk('local')->exists($zipPath)) {
            abort(404, 'Export file not found.');
        }

        $filename = basename($zipPath);

        return response(Storage::disk('local')->get($zipPath), 200, [
            'Content-Type' => 'application/zip',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
