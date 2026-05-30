<?php

declare(strict_types=1);

namespace App\Services\Fdd;

use App\Models\Document;
use App\Models\Fdd;
use App\Support\MinimalPdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FddPdfService
{
    public function ensureDocument(Fdd $fdd): Document
    {
        if ($fdd->document_id !== null) {
            $existing = Document::query()->find($fdd->document_id);

            if ($existing !== null) {
                return $existing;
            }
        }

        $path = $this->writePlaceholderPdf($fdd);

        $document = Document::query()->create([
            'title' => $fdd->title.' (PDF)',
            'mime_type' => 'application/pdf',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'status' => 'active',
            'extras' => [
                'generated' => true,
                'placeholder' => true,
                'fdd_id' => $fdd->id,
            ],
        ]);

        $fdd->update(['document_id' => $document->id]);

        return $document;
    }

    private function writePlaceholderPdf(Fdd $fdd): string
    {
        $filename = 'fdd-'.$fdd->id.'-'.Str::slug($fdd->title).'.pdf';
        $path = 'documents/fdd/'.$filename;

        $content = MinimalPdf::generate($fdd->title);
        Storage::disk('local')->put($path, $content);

        return $path;
    }
}
