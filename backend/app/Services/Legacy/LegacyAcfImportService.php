<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Legacy\LegacyAcfGroupRegistry;
use Illuminate\Support\Str;

final class LegacyAcfImportService
{
    public function __construct(
        private readonly LegacyAcfGroupRegistry $groups,
    ) {}

    /**
     * @return array{groups: int, fields: int, skipped_groups: int}
     */
    public function importDirectory(string $path, string $defaultEntity = 'lead'): array
    {
        $importedGroups = 0;
        $importedFields = 0;
        $skippedGroups = 0;
        $sortCounters = [];

        foreach (glob($path.'/*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);

            if (! is_array($json) || ! isset($json['key'], $json['title'])) {
                continue;
            }

            $legacyKey = (string) $json['key'];
            $meta = $this->groups->resolve($legacyKey, (string) $json['title'], $json['location'] ?? []);

            if ($meta === null) {
                $skippedGroups++;

                continue;
            }

            $entity = $meta['entity'] ?: $defaultEntity;
            $groupKey = $meta['key'];

            $group = FieldGroup::query()->firstOrCreate(
                ['key' => $groupKey],
                [
                    'legacy_group_key' => $legacyKey,
                    'title' => $meta['title'],
                    'slug' => $groupKey,
                    'sort_order' => $meta['sort_order'],
                    'location_rules' => $json['location'] ?? null,
                    'status' => 'active',
                ],
            );

            if ($group->legacy_group_key === null) {
                $group->legacy_group_key = $legacyKey;
            }

            $group->fill([
                'title' => $meta['title'],
                'sort_order' => min($group->sort_order ?: 999, $meta['sort_order']),
                'location_rules' => $json['location'] ?? $group->location_rules,
                'status' => 'active',
            ])->save();

            if (! isset($sortCounters[$groupKey])) {
                $sortCounters[$groupKey] = (int) Field::query()->where('field_group_id', $group->id)->max('sort_order');
                $importedGroups++;
            }

            $importedFields += $this->importFieldTree(
                $json['fields'] ?? [],
                $group,
                $entity,
                $sortCounters,
                $groupKey,
            );
        }

        return [
            'groups' => $importedGroups,
            'fields' => $importedFields,
            'skipped_groups' => $skippedGroups,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $acfFields
     * @param  array<string, int>  $sortCounters
     */
    private function importFieldTree(
        array $acfFields,
        FieldGroup $group,
        string $entity,
        array &$sortCounters,
        string $groupKey,
        string $keyPrefix = '',
    ): int {
        $imported = 0;

        foreach ($acfFields as $acfField) {
            if (! is_array($acfField)) {
                continue;
            }

            $type = (string) ($acfField['type'] ?? 'text');
            $name = (string) ($acfField['name'] ?? '');

            if (in_array($type, $this->skipTypes(), true)) {
                continue;
            }

            if ($type === 'group' && is_array($acfField['sub_fields'] ?? null)) {
                $imported += $this->importFieldTree(
                    $acfField['sub_fields'],
                    $group,
                    $entity,
                    $sortCounters,
                    $groupKey,
                    $keyPrefix,
                );

                continue;
            }

            if ($name === '') {
                if ($type === 'repeater' && is_array($acfField['sub_fields'] ?? null)) {
                    continue;
                }

                continue;
            }

            $fieldKey = $this->normalizeFieldKey($keyPrefix !== '' ? $keyPrefix.$name : $name);

            if ($this->shouldSkipField($fieldKey, $entity)) {
                continue;
            }

            if (! $this->validFieldKey($fieldKey)) {
                continue;
            }

            $sortCounters[$groupKey] = ($sortCounters[$groupKey] ?? 0) + 1;
            $filType = $this->mapType($type);
            $tierOne = $this->tierOneColumn($entity, $fieldKey);
            $choices = isset($acfField['choices']) && is_array($acfField['choices'])
                ? $this->flattenChoices($acfField['choices'])
                : null;

            $config = array_filter([
                'choices' => $choices,
                'legacy_field_key' => $acfField['key'] ?? null,
                'legacy_acf_type' => $type,
                'related_entity' => $this->relationEntity($acfField, $filType),
            ]);

            $existing = Field::query()
                ->where('field_group_id', $group->id)
                ->where('key', $fieldKey)
                ->first();

            if ($existing !== null && $this->isSystemField($entity, $fieldKey)) {
                $config = $this->mergeSystemFieldConfig($existing, $config);
            }

            Field::query()->updateOrCreate(
                [
                    'field_group_id' => $group->id,
                    'key' => $fieldKey,
                ],
                [
                    'entity' => $entity,
                    'name' => (string) ($acfField['label'] ?? $name),
                    'type' => $filType,
                    'storage' => $tierOne !== null ? 'column' : 'field_value',
                    'maps_to_column' => $tierOne,
                    'config' => $config !== [] ? $config : null,
                    'sort_order' => $sortCounters[$groupKey],
                    'is_filterable' => $tierOne !== null && in_array($fieldKey, $this->filterableKeys(), true),
                    'status' => 'active',
                    'legacy_field_key' => $acfField['key'] ?? null,
                ],
            );

            $imported++;
        }

        return $imported;
    }

    /**
     * @return list<string>
     */
    private function skipTypes(): array
    {
        /** @var list<string> */
        return config('fil-legacy-acf.skip_field_types', ['tab', 'message', 'accordion']);
    }

    /**
     * @return list<string>
     */
    private function filterableKeys(): array
    {
        /** @var list<string> */
        return config('fil-legacy-acf.filterable_keys', []);
    }

    private function shouldSkipField(string $key, string $entity): bool
    {
        /** @var list<string> $skip */
        $skip = config('fil-legacy-acf.skip_field_keys', []);

        if (in_array($key, $skip, true)) {
            return true;
        }

        if ($entity === 'lead') {
            /** @var list<string> $excluded */
            $excluded = config('fil-legacy-acf.excluded_lead_field_keys', []);

            if (in_array($key, $excluded, true)) {
                return true;
            }
        }

        return false;
    }

    private function tierOneColumn(string $entity, string $key): ?string
    {
        /** @var array<string, array<string, string>> $map */
        $map = config('fil-legacy-acf.tier1_columns', []);

        return ($map[$entity] ?? [])[$key] ?? null;
    }

    private function mapType(string $acfType): string
    {
        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.type_map', []);

        return $map[$acfType] ?? 'text';
    }

    /**
     * @param  array<string, mixed>  $acfField
     */
    private function relationEntity(array $acfField, string $filType): ?string
    {
        if ($filType !== 'relation_one' && $filType !== 'relation_many') {
            return null;
        }

        if (($acfField['type'] ?? '') === 'user') {
            return 'user';
        }

        $postTypes = $acfField['post_type'] ?? null;

        if (! is_array($postTypes) || $postTypes === []) {
            return null;
        }

        $legacyType = (string) $postTypes[0];

        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.post_type_relations', []);

        return $map[$legacyType] ?? null;
    }

    private function validFieldKey(string $key): bool
    {
        return (bool) preg_match('/^[a-z][a-z0-9_]*$/', $key);
    }

    private function normalizeFieldKey(string $name): string
    {
        $key = Str::snake(str_replace(['-', ' '], '_', $name));
        $key = strtolower($key);

        return (string) preg_replace('/_+/', '_', $key);
    }

    /**
     * @param  array<mixed, mixed>  $choices
     * @return list<array{value: string, label: string}>
     */
    private function flattenChoices(array $choices): array
    {
        $flat = [];

        foreach ($choices as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $nestedKey => $nestedLabel) {
                    $flat[] = [
                        'value' => (string) $nestedKey,
                        'label' => (string) $nestedLabel,
                    ];
                }

                continue;
            }

            $flat[] = [
                'value' => (string) $key,
                'label' => (string) $value,
            ];
        }

        return $flat;
    }

    private function isSystemField(string $entity, string $fieldKey): bool
    {
        /** @var array<string, array<string, mixed>> $keys */
        $keys = config('fil-fields.system_keys', []);

        return isset($keys[$entity][$fieldKey]);
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeSystemFieldConfig(Field $existing, array $incoming): array
    {
        $current = is_array($existing->config) ? $existing->config : [];

        if (($current['choices'] ?? []) !== []) {
            unset($incoming['choices']);
        }

        return array_replace($current, array_filter($incoming, static fn ($value): bool => $value !== null));
    }
}
