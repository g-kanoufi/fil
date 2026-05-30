<?php

declare(strict_types=1);

namespace App\Support;

final class MinimalPdf
{
    public static function generate(string $title): string
    {
        $safeTitle = self::escapeText(mb_substr(trim($title) !== '' ? trim($title) : 'Document', 0, 120));

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>\nendobj\n",
            "4 0 obj\n<< /Length ".strlen("BT /F1 18 Tf 72 720 Td ({$safeTitle}) Tj ET")." >>\nstream\nBT /F1 18 Tf 72 720 Td ({$safeTitle}) Tj ET\nendstream\nendobj\n",
            "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 ".count($offsets)."\n";
        $pdf .= "0000000000 65535 f \n";

        for ($index = 1; $index < count($offsets); $index++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$index]);
        }

        $pdf .= "trailer\n<< /Size ".count($offsets)." /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    public static function isValid(string $contents): bool
    {
        if (! str_starts_with($contents, '%PDF-')) {
            return false;
        }

        return str_contains($contents, 'startxref') && str_contains($contents, '%%EOF');
    }

    private static function escapeText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
