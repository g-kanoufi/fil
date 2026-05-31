<?php

declare(strict_types=1);

namespace App\Support\Embed;

use Illuminate\Http\Request;

/**
 * Validates browser Origin / Referer for public embed intake in staging and production.
 */
final class EmbedOriginGuard
{
    /**
     * @param  list<string>  $allowedOrigins  From config('fil.embed_allowed_origins')
     */
    public function allows(Request $request, array $allowedOrigins): bool
    {
        if (! app()->environment('production', 'staging')) {
            return true;
        }

        if ($allowedOrigins === []) {
            return false;
        }

        $origin = $this->resolveOrigin($request);

        if ($origin === null) {
            return false;
        }

        $normalized = $this->normalizeOrigin($origin);

        if ($this->isAppOrigin($normalized)) {
            return true;
        }

        foreach ($allowedOrigins as $allowed) {
            if ($this->normalizeOrigin($allowed) === $normalized) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $allowedOrigins
     */
    public function rejectionMessage(array $allowedOrigins): string
    {
        if ($allowedOrigins === []) {
            return 'Embed origin allowlist is not configured.';
        }

        return 'Origin not allowed.';
    }

    private function resolveOrigin(Request $request): ?string
    {
        $origin = $request->headers->get('Origin');

        if (is_string($origin) && $origin !== '') {
            return $origin;
        }

        $referer = $request->headers->get('Referer');

        if (! is_string($referer) || $referer === '') {
            return null;
        }

        $parts = parse_url($referer);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return null;
        }

        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $parts['scheme'].'://'.$parts['host'].$port;
    }

    private function normalizeOrigin(string $origin): string
    {
        return rtrim(strtolower(trim($origin)), '/');
    }

    private function isAppOrigin(string $normalizedOrigin): bool
    {
        $appUrl = config('app.url');

        if (! is_string($appUrl) || $appUrl === '') {
            return false;
        }

        return $this->normalizeOrigin($appUrl) === $normalizedOrigin;
    }
}
