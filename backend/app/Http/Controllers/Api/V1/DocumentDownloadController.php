<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Document as DocumentModel;
use App\Support\DocumentFilename;
use App\Support\MinimalPdf;
use App\Support\PreviewUrlValidator;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Storage;

final class DocumentDownloadController extends Controller
{
    public function __invoke(DocumentModel $document, PreviewUrlValidator $previewUrls): StreamedResponse|RedirectResponse
    {
        $this->authorize('view', $document);

        $previewUrl = data_get($document->extras, 'preview_url');

        if (data_get($document->extras, 'url_only') && is_string($previewUrl) && $previewUrl !== '') {
            abort_unless($previewUrls->isAllowed($previewUrl), 403, 'Preview URL is not allowed.');

            return redirect()->away($previewUrl);
        }

        if (! Storage::disk($document->storage_disk)->exists($document->storage_path)) {
            abort(404, 'Document file not found.');
        }

        $this->repairPlaceholderIfNeeded($document);

        $filename = DocumentFilename::for($document);
        $mimeType = $document->mime_type ?? 'application/octet-stream';

        return Storage::disk($document->storage_disk)->response(
            $document->storage_path,
            $filename,
            [
                'Content-Type' => $mimeType,
                'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            ],
        );
    }

    private function repairPlaceholderIfNeeded(DocumentModel $document): void
    {
        if (! data_get($document->extras, 'placeholder')) {
            return;
        }

        $disk = Storage::disk($document->storage_disk);
        $contents = (string) $disk->get($document->storage_path);

        if (MinimalPdf::isValid($contents)) {
            return;
        }

        $disk->put($document->storage_path, MinimalPdf::generate($document->title));
    }
}
