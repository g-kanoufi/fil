<?php

declare(strict_types=1);

namespace App\Services\Fdd;

use App\Models\Document;
use App\Models\Fdd;
use App\Models\User;
use App\Support\MinimalPdf;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class FddDocumentService
{
    public function storeFromUpload(UploadedFile $file, Fdd $fdd, User $user): Document
    {
        $path = $this->writePdf($file, $fdd);

        return Document::query()->create([
            'title' => $fdd->title,
            'mime_type' => 'application/pdf',
            'storage_disk' => 'local',
            'storage_path' => $path,
            'file_size' => $file->getSize(),
            'checksum' => hash_file('sha256', $file->getRealPath()) ?: null,
            'status' => 'active',
            'uploaded_by_user_id' => $user->id,
            'extras' => [
                'fdd_id' => $fdd->id,
                'original_filename' => $file->getClientOriginalName(),
            ],
        ]);
    }

    public function replaceForFdd(UploadedFile $file, Fdd $fdd, User $user): Document
    {
        $existing = $fdd->document_id !== null
            ? Document::query()->find($fdd->document_id)
            : null;

        $path = $this->writePdf($file, $fdd);
        $checksum = hash_file('sha256', $file->getRealPath()) ?: null;

        if ($existing !== null) {
            $previousPath = $existing->storage_path;

            $existing->update([
                'title' => $fdd->title,
                'mime_type' => 'application/pdf',
                'storage_path' => $path,
                'file_size' => $file->getSize(),
                'checksum' => $checksum,
                'uploaded_by_user_id' => $user->id,
                'extras' => array_merge($existing->extras ?? [], [
                    'fdd_id' => $fdd->id,
                    'original_filename' => $file->getClientOriginalName(),
                ]),
            ]);

            if ($previousPath !== $path) {
                Storage::disk('local')->delete($previousPath);
            }

            return $existing->fresh();
        }

        $document = $this->storeFromUpload($file, $fdd, $user);
        $fdd->update(['document_id' => $document->id]);

        return $document;
    }

    private function writePdf(UploadedFile $file, Fdd $fdd): string
    {
        $contents = (string) file_get_contents($file->getRealPath());

        if (! MinimalPdf::isValid($contents)) {
            throw new \InvalidArgumentException('Uploaded file is not a valid PDF.');
        }

        $basename = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = 'fdd-'.$fdd->id.'-'.($basename !== '' ? $basename : 'document').'.pdf';
        $path = 'documents/fdd/'.$filename;

        Storage::disk('local')->put($path, (string) file_get_contents($file->getRealPath()));

        return $path;
    }
}
