<?php

declare(strict_types=1);

namespace App\Services\Activity;

final class NavigationPathResolver
{
    /**
     * @return array{type: string|null, id: int|null}
     */
    public function resolveSubject(string $pathKey): array
    {
        if (preg_match('#^/reports/leads/(\d+)$#', $pathKey, $matches) === 1) {
            return ['type' => 'lead', 'id' => (int) $matches[1]];
        }

        if (preg_match('#^/reports/stores/(\d+)$#', $pathKey, $matches) === 1) {
            return ['type' => 'store', 'id' => (int) $matches[1]];
        }

        if (preg_match('#^/reports/contacts/(\d+)$#', $pathKey, $matches) === 1) {
            return ['type' => 'contact', 'id' => (int) $matches[1]];
        }

        if (preg_match('#^/reports/closings/(\d+)$#', $pathKey, $matches) === 1) {
            return ['type' => 'closing', 'id' => (int) $matches[1]];
        }

        if (preg_match('#^/documents/(\d+)$#', $pathKey, $matches) === 1) {
            return ['type' => 'document', 'id' => (int) $matches[1]];
        }

        return ['type' => null, 'id' => null];
    }

    public function normalizePath(string $path): string
    {
        $path = parse_url($path, PHP_URL_PATH) ?? $path;
        $path = '/'.trim((string) $path, '/');

        if ($path === '/') {
            return '/';
        }

        return rtrim($path, '/') ?: '/';
    }

    public function isAllowed(string $pathKey): bool
    {
        $gridPaths = config('fil-activity.navigation.grid_paths_exact', []);

        if (in_array($pathKey, $gridPaths, true)) {
            return false;
        }

        /** @var list<string> $prefixes */
        $prefixes = config('fil-activity.navigation.allowlist_prefixes', []);

        foreach ($prefixes as $prefix) {
            if ($prefix === '/' && $pathKey === '/') {
                return true;
            }

            if ($prefix !== '/' && str_starts_with($pathKey, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
