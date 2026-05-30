<?php

declare(strict_types=1);

namespace App\Support;

final class PreviewUrlValidator
{
    public function isAllowed(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts)) {
            return false;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));

        if (! in_array($scheme, ['https', 'http'], true)) {
            return false;
        }

        $host = strtolower((string) ($parts['host'] ?? ''));

        if ($host === '' || $this->isPrivateHost($host)) {
            return false;
        }

        /** @var list<string> $allowedHosts */
        $allowedHosts = config('fil.documents.preview_url_hosts', []);

        if ($allowedHosts === []) {
            return app()->environment('local', 'testing');
        }

        foreach ($allowedHosts as $allowedHost) {
            $allowedHost = strtolower($allowedHost);

            if ($host === $allowedHost || str_ends_with($host, '.'.ltrim($allowedHost, '.'))) {
                return true;
            }
        }

        return false;
    }

    private function isPrivateHost(string $host): bool
    {
        if ($host === 'localhost' || str_ends_with($host, '.local')) {
            return true;
        }

        if (filter_var($host, FILTER_VALIDATE_IP) === false) {
            return false;
        }

        return ! filter_var(
            $host,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE,
        );
    }
}
