<?php

declare(strict_types=1);

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

/**
 * Validates that an uploaded file is really a PDF by inspecting its magic bytes,
 * not just the client-supplied MIME type / extension (SEC-025).
 */
final class PdfFile implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $path = $value->getRealPath();
        $handle = $path !== false ? @fopen($path, 'rb') : false;

        if ($handle === false) {
            $fail('The :attribute could not be read.');

            return;
        }

        $header = (string) fread($handle, 5);
        fclose($handle);

        if (! str_starts_with($header, '%PDF-')) {
            $fail('The :attribute must be a valid PDF file.');
        }
    }
}
