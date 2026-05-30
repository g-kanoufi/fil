<?php

declare(strict_types=1);

namespace App\Support;

final class HtmlSanitizer
{
    public function sanitize(?string $html): ?string
    {
        if ($html === null || $html === '') {
            return $html;
        }

        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*>.*?</\1>#is', '', $html) ?? $html;
        $html = preg_replace('#<(script|style|iframe|object|embed|link|meta)\b[^>]*/?>#is', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*("|\').*?\1/iu', '', $html) ?? $html;
        $html = preg_replace('/\son\w+\s*=\s*[^\s>]+/iu', '', $html) ?? $html;
        $html = preg_replace('/\s(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/iu', '', $html) ?? $html;

        return trim($html);
    }
}
