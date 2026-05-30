<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyAcfFilePatternBuilder
{
    /**
     * @return list<array{role: string, label: string, regex: string}>
     */
    public function fromJsonFile(string $path): array
    {
        if (! is_readable($path)) {
            return [];
        }

        $payload = json_decode((string) file_get_contents($path), true);

        if (! is_array($payload)) {
            return [];
        }

        $patterns = [];
        $this->collect((array) ($payload['fields'] ?? []), '', '', $patterns);

        return $patterns;
    }

    /**
     * @param  list<array<string, mixed>>  $fields
     * @param  list<array{role: string, label: string, regex: string}>  $patterns
     */
    private function collect(array $fields, string $prefix, string $parentLabel, array &$patterns): void
    {
        foreach ($fields as $field) {
            if (! is_array($field) || empty($field['type'])) {
                continue;
            }

            $type = (string) $field['type'];
            $name = (string) ($field['name'] ?? '');
            $label = (string) ($field['label'] ?? $name);
            $key = (string) ($field['key'] ?? $name);

            if ($name === '') {
                continue;
            }

            $currentPrefix = $prefix !== '' ? $prefix.'_'.$name : $name;

            if ($type === 'file') {
                $metaPattern = $prefix !== '' ? $prefix.'_%_'.$name : $name;
                $patterns[] = [
                    'role' => $this->roleFromMetaPattern($metaPattern, $name),
                    'field_key' => $key,
                    'label' => $this->labelPath($label, $parentLabel),
                    'regex' => $this->patternToRegex($metaPattern),
                ];

                continue;
            }

            if (in_array($type, ['repeater', 'group'], true) && ! empty($field['sub_fields']) && is_array($field['sub_fields'])) {
                $this->collect(
                    $field['sub_fields'],
                    $currentPrefix,
                    $this->labelPath($label, $parentLabel),
                    $patterns,
                );
            }

            if ($type === 'flexible_content' && ! empty($field['layouts']) && is_array($field['layouts'])) {
                $layoutParent = $this->labelPath($label, $parentLabel);

                foreach ($field['layouts'] as $layout) {
                    if (! is_array($layout) || empty($layout['sub_fields']) || ! is_array($layout['sub_fields'])) {
                        continue;
                    }

                    $layoutLabel = $layoutParent.' > '.((string) ($layout['label'] ?? $layout['name'] ?? ''));

                    $this->collect($layout['sub_fields'], $currentPrefix, $layoutLabel, $patterns);
                }
            }
        }
    }

    private function labelPath(string $label, string $parentLabel): string
    {
        return $parentLabel !== '' ? $parentLabel.' > '.$label : $label;
    }

    private function patternToRegex(string $metaPattern): string
    {
        if (str_contains($metaPattern, '%')) {
            $quoted = preg_quote($metaPattern, '/');
            $quoted = str_replace('%', '(\d+)', $quoted);

            return '/^'.$quoted.'$/';
        }

        return '/^'.preg_quote($metaPattern, '/').'$/';
    }

    private function roleFromMetaPattern(string $metaPattern, string $fieldName): string
    {
        if (str_contains($metaPattern, '%')) {
            $role = preg_replace('/_%_'.preg_quote($fieldName, '/').'$/', '', $metaPattern);

            return is_string($role) && $role !== '' ? $role : $fieldName;
        }

        return $metaPattern;
    }
}
