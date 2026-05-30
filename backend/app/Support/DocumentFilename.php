<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Document;

final class DocumentFilename
{
    public static function for(Document $document): string
    {
        $original = data_get($document->extras, 'original_filename');

        if (is_string($original) && $original !== '') {
            return basename($original);
        }

        $pathName = basename($document->storage_path);

        if ($pathName !== '' && $pathName !== '.') {
            return $pathName;
        }

        $extension = self::extensionForMime($document->mime_type);

        return 'document-'.$document->id.$extension;
    }

    private static function extensionForMime(?string $mimeType): string
    {
        return match ($mimeType) {
            'application/pdf' => '.pdf',
            'application/zip' => '.zip',
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            default => '',
        };
    }
}
