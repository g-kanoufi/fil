<?php

declare(strict_types=1);

namespace App\Support\Legacy;

use App\Models\Field;
use Illuminate\Support\Collection;

/**
 * Expected FIL field keys from bundled legacy ACF JSON (before DB import).
 */
final class LegacyBundledAcfFieldCatalog
{
    public function __construct(
        private readonly LegacyAcfGroupRegistry $groups,
    ) {}

    /**
     * @return list<string>
     */
    public function fieldKeysForPostType(string $legacyPostType, string $entity): array
    {
        $keys = [];

        foreach ($this->groupJsonPaths() as $path) {
            $json = $this->readGroupJson($path);

            if ($json === null) {
                continue;
            }

            $legacyKey = (string) $json['key'];
            $meta = $this->resolveGroupMeta($legacyKey, $json, $legacyPostType);

            if ($meta === null || $meta['entity'] !== $entity) {
                continue;
            }

            if (($meta['legacy_post_type'] ?? '') !== '' && $meta['legacy_post_type'] !== $legacyPostType) {
                continue;
            }

            $index = collect();
            $this->collectFields((array) ($json['fields'] ?? []), $entity, $index);
            $keys = array_merge($keys, $index->keys()->all());
        }

        return array_values(array_unique($keys));
    }

    /**
     * @return Collection<string, Field>
     */
    public function fieldIndexForEntity(string $entity, ?string $legacyPostType = null): Collection
    {
        /** @var Collection<string, Field> $index */
        $index = collect();

        foreach ($this->groupJsonPaths() as $path) {
            $json = $this->readGroupJson($path);

            if ($json === null) {
                continue;
            }

            $legacyKey = (string) $json['key'];
            /** @var array<string, array<string, mixed>> $configured */
            $configured = config('fil-legacy-acf.groups', []);
            $variants = $configured[$legacyKey]['post_type_variants'] ?? null;

            if (is_array($variants) && $variants !== []) {
                foreach ($variants as $postType => $variant) {
                    if (($variant['import'] ?? true) === false) {
                        continue;
                    }

                    if ($legacyPostType !== null && $postType !== $legacyPostType) {
                        continue;
                    }

                    $meta = $this->groups->resolve(
                        $legacyKey,
                        (string) $json['title'],
                        $json['location'] ?? [],
                        $postType,
                        $variant,
                    );

                    if ($meta === null || $meta['entity'] !== $entity) {
                        continue;
                    }

                    $this->collectFields((array) ($json['fields'] ?? []), $entity, $index);
                }

                continue;
            }

            $meta = $this->groups->resolve(
                $legacyKey,
                (string) $json['title'],
                $json['location'] ?? [],
                $legacyPostType,
            );

            if ($meta === null || $meta['entity'] !== $entity) {
                continue;
            }

            if ($legacyPostType !== null
                && ($meta['legacy_post_type'] ?? '') !== ''
                && $meta['legacy_post_type'] !== $legacyPostType) {
                continue;
            }

            $this->collectFields((array) ($json['fields'] ?? []), $entity, $index);
        }

        return $index;
    }

    /**
     * @return list<string>
     */
    private function groupJsonPaths(): array
    {
        return glob(base_path('resources/legacy-acf/group_*.json')) ?: [];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readGroupJson(string $path): ?array
    {
        $json = json_decode((string) file_get_contents($path), true);

        if (! is_array($json) || ! isset($json['key'], $json['title'])) {
            return null;
        }

        return $json;
    }

    /**
     * @param  array<string, mixed>  $json
     * @return array{import: bool, key: string, title: string, entity: string, legacy_post_type: string, sort_order: int, merge_into?: string}|null
     */
    private function resolveGroupMeta(string $legacyKey, array $json, string $legacyPostType): ?array
    {
        /** @var array<string, array<string, mixed>> $configured */
        $configured = config('fil-legacy-acf.groups', []);
        $variants = $configured[$legacyKey]['post_type_variants'] ?? null;

        if (is_array($variants) && isset($variants[$legacyPostType])) {
            if (($variants[$legacyPostType]['import'] ?? true) === false) {
                return null;
            }

            return $this->groups->resolve(
                $legacyKey,
                (string) $json['title'],
                $json['location'] ?? [],
                $legacyPostType,
                $variants[$legacyPostType],
            );
        }

        return $this->groups->resolve(
            $legacyKey,
            (string) $json['title'],
            $json['location'] ?? [],
            $legacyPostType,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $acfFields
     * @param  Collection<string, Field>  $index
     */
    private function collectFields(array $acfFields, string $entity, Collection $index): void
    {
        foreach ($acfFields as $acfField) {
            if (! is_array($acfField) || empty($acfField['type'])) {
                continue;
            }

            $type = (string) $acfField['type'];
            $name = (string) ($acfField['name'] ?? '');

            if ($name === '' || in_array($name, config('fil-legacy-acf.skip_field_keys', []), true)) {
                continue;
            }

            if (in_array($type, config('fil-legacy-acf.skip_field_types', []), true)) {
                continue;
            }

            if ($entity === 'lead' && in_array($name, config('fil-legacy-acf.excluded_lead_field_keys', []), true)) {
                continue;
            }

            if (! $index->has($name)) {
                $field = new Field([
                    'key' => $name,
                    'entity' => $entity,
                    'config' => ['legacy_acf_type' => $type],
                ]);
                $index->put($name, $field);
            }

            if (in_array($type, ['repeater', 'group'], true) && is_array($acfField['sub_fields'] ?? null)) {
                $this->collectFields($acfField['sub_fields'], $entity, $index);
            }

            if ($type === 'flexible_content' && is_array($acfField['layouts'] ?? null)) {
                foreach ($acfField['layouts'] as $layout) {
                    if (! is_array($layout) || ! is_array($layout['sub_fields'] ?? null)) {
                        continue;
                    }

                    $this->collectFields($layout['sub_fields'], $entity, $index);
                }
            }
        }
    }
}
