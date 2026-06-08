<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use Illuminate\Support\Str;

/**
 * Maps flattened WordPress/ACF postmeta keys to FIL field keys.
 *
 * ACF stores repeaters as {@code parent_0_subfield} and groups as {@code group_subfield}.
 */
final class LegacyAcfMetaKeyCatalog
{
    /** @var list<string>|null */
    private ?array $groupPrefixes = null;

    /**
     * @return list<string> Longest-first group meta prefixes (include trailing underscore).
     */
    public function groupPrefixes(): array
    {
        if ($this->groupPrefixes !== null) {
            return $this->groupPrefixes;
        }

        $prefixes = [];

        foreach ($this->legacyAcfJsonPaths() as $path) {
            $payload = json_decode((string) file_get_contents($path), true);

            if (! is_array($payload)) {
                continue;
            }

            $this->collectGroupPrefixes((array) ($payload['fields'] ?? []), '', $prefixes);
        }

        $prefixes = array_values(array_unique($prefixes));
        usort($prefixes, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));

        return $this->groupPrefixes = $prefixes;
    }

    public function normalizeMetaKey(string $metaKey): string
    {
        $key = Str::snake(str_replace(['-', ' '], '_', $metaKey));
        $key = strtolower($key);

        return (string) preg_replace('/_+/', '_', $key);
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  list<string>  $prefixes
     */
    private function collectGroupPrefixes(array $fields, string $prefix, array &$prefixes): void
    {
        foreach ($fields as $field) {
            if (! is_array($field) || empty($field['type'])) {
                continue;
            }

            $type = (string) $field['type'];
            $name = (string) ($field['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $currentPrefix = $prefix !== '' ? $prefix.'_'.$name : $name;

            if ($type === 'group' && is_array($field['sub_fields'] ?? null)) {
                $prefixes[] = $currentPrefix.'_';
                $this->collectGroupPrefixes($field['sub_fields'], $currentPrefix, $prefixes);

                continue;
            }

            if ($type === 'repeater' && is_array($field['sub_fields'] ?? null)) {
                continue;
            }

            if ($type === 'flexible_content' && is_array($field['layouts'] ?? null)) {
                foreach ($field['layouts'] as $layout) {
                    if (! is_array($layout) || ! is_array($layout['sub_fields'] ?? null)) {
                        continue;
                    }

                    $this->collectGroupPrefixes($layout['sub_fields'], $currentPrefix, $prefixes);
                }
            }
        }
    }

    /**
     * @return list<string>
     */
    private function legacyAcfJsonPaths(): array
    {
        $paths = glob(base_path('resources/legacy-acf/group_*.json')) ?: [];

        return array_values(array_filter($paths, static fn (string $path): bool => is_readable($path)));
    }
}
