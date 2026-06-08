<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Fields\FieldTypes;
use App\Support\Legacy\LegacyAcfGroupRegistry;
use App\Support\Widget\WidgetFieldEligibility;
use Illuminate\Support\Str;

final class LegacyAcfImportService
{
    public function __construct(
        private readonly LegacyAcfGroupRegistry $groups,
    ) {}

    /**
     * @return array{groups: int, fields: int, skipped_groups: int}
     */
    public function importDirectory(string $path): array
    {
        $importedGroups = 0;
        $importedFields = 0;
        $skippedGroups = 0;
        $sortCounters = [];
        $seenGroupKeys = [];
        $touchedFieldIds = [];

        foreach ($this->sortedImportPlans($path) as $plan) {
            if ($plan['meta'] === null) {
                $skippedGroups++;

                continue;
            }

            [$importedGroups, $importedFields] = $this->importResolvedGroup(
                $plan['json'],
                $plan['legacyKey'],
                $plan['meta'],
                $sortCounters,
                $seenGroupKeys,
                $touchedFieldIds,
                $importedGroups,
                $importedFields,
            );
        }

        $this->deactivateStaleImportedFields($seenGroupKeys, $touchedFieldIds);

        return [
            'groups' => $importedGroups,
            'fields' => $importedFields,
            'skipped_groups' => $skippedGroups,
        ];
    }

    /**
     * @return list<array{legacyKey: string, json: array<string, mixed>, meta: array{import: bool, key: string, title: string, entity: string, legacy_post_type: string, sort_order: int, merge_into?: string|null}|null}>
     */
    private function sortedImportPlans(string $path): array
    {
        $plans = [];

        foreach (glob($path.'/group_*.json') ?: [] as $file) {
            $json = json_decode((string) file_get_contents($file), true);

            if (! is_array($json) || ! isset($json['key'], $json['title'])) {
                continue;
            }

            $legacyKey = (string) $json['key'];
            $location = $json['location'] ?? [];
            /** @var array<string, array<string, mixed>> $configuredGroups */
            $configuredGroups = config('fil-legacy-acf.groups', []);
            $groupConfig = $configuredGroups[$legacyKey] ?? [];
            /** @var array<string, array<string, mixed>>|null $variants */
            $variants = $groupConfig['post_type_variants'] ?? null;

            if (is_array($variants) && $variants !== []) {
                foreach ($variants as $postType => $variant) {
                    if (($variant['import'] ?? true) === false) {
                        continue;
                    }

                    $meta = $this->groups->resolve($legacyKey, (string) $json['title'], $location, (string) $postType, $variant);

                    if ($meta === null) {
                        continue;
                    }

                    $plans[] = ['legacyKey' => $legacyKey, 'json' => $json, 'meta' => $meta];
                }

                continue;
            }

            $postTypes = $this->groups->postTypesFromLocation($location);

            if (count($postTypes) <= 1) {
                $meta = $this->groups->resolve($legacyKey, (string) $json['title'], $location);

                $plans[] = ['legacyKey' => $legacyKey, 'json' => $json, 'meta' => $meta];

                continue;
            }

            foreach ($postTypes as $postType) {
                $meta = $this->groups->resolve($legacyKey, (string) $json['title'], $location, $postType);

                if ($meta === null) {
                    continue;
                }

                if ($meta['legacy_post_type'] === '') {
                    $meta['legacy_post_type'] = $postType;
                }

                if (! isset($groupConfig['key']) && ! isset($groupConfig['merge_into'])) {
                    $meta['key'] = $meta['key'].'-'.str_replace('_', '-', $postType);
                    $meta['title'] = $meta['title'].' ('.$postType.')';
                }

                $plans[] = ['legacyKey' => $legacyKey, 'json' => $json, 'meta' => $meta];
            }
        }

        usort(
            $plans,
            static function (array $left, array $right): int {
                $leftMeta = $left['meta'];
                $rightMeta = $right['meta'];

                if ($leftMeta === null && $rightMeta === null) {
                    return strcmp($left['legacyKey'], $right['legacyKey']);
                }

                if ($leftMeta === null) {
                    return 1;
                }

                if ($rightMeta === null) {
                    return -1;
                }

                $groupKeyCompare = strcmp($leftMeta['key'], $rightMeta['key']);

                if ($groupKeyCompare !== 0) {
                    return $groupKeyCompare;
                }

                $sortOrderCompare = $leftMeta['sort_order'] <=> $rightMeta['sort_order'];

                if ($sortOrderCompare !== 0) {
                    return $sortOrderCompare;
                }

                return strcmp($left['legacyKey'], $right['legacyKey']);
            },
        );

        return $plans;
    }

    /**
     * @param  array<string, mixed>  $json
     * @param  array{import: bool, key: string, title: string, entity: string, legacy_post_type: string, sort_order: int, merge_into?: string|null}  $meta
     * @param  array<string, int>  $sortCounters
     * @param  array<string, true>  $seenGroupKeys
     * @param  list<int>  $touchedFieldIds
     * @return array{0: int, 1: int}
     */
    private function importResolvedGroup(
        array $json,
        string $legacyKey,
        array $meta,
        array &$sortCounters,
        array &$seenGroupKeys,
        array &$touchedFieldIds,
        int $importedGroups,
        int $importedFields,
    ): array {
        $entity = $meta['entity'] ?: 'lead';
        $groupKey = $meta['key'];

        $group = FieldGroup::query()->firstOrCreate(
            ['key' => $groupKey],
            [
                'legacy_group_key' => $this->uniqueLegacyGroupKey($legacyKey, $groupKey),
                'title' => $meta['title'],
                'slug' => $groupKey,
                'sort_order' => $meta['sort_order'],
                'location_rules' => $json['location'] ?? null,
                'status' => 'active',
            ],
        );

        if ($group->legacy_group_key === null) {
            $group->legacy_group_key = $this->uniqueLegacyGroupKey($legacyKey, $groupKey);
        }

        $group->fill([
            'title' => $meta['title'],
            'sort_order' => min($group->sort_order ?: 999, $meta['sort_order']),
            'location_rules' => $json['location'] ?? $group->location_rules,
            'status' => 'active',
        ])->save();

        if (! isset($seenGroupKeys[$groupKey])) {
            $seenGroupKeys[$groupKey] = true;
            $importedGroups++;
        }

        $sortCounters[$groupKey] ??= 0;

        $importedFields += $this->importFieldTree(
            $json['fields'] ?? [],
            $group,
            $entity,
            (string) ($meta['legacy_post_type'] ?? ''),
            $sortCounters,
            $groupKey,
            '',
            0,
            $touchedFieldIds,
        );

        return [$importedGroups, $importedFields];
    }

    private function uniqueLegacyGroupKey(string $legacyKey, string $groupKey): ?string
    {
        $claimed = FieldGroup::query()
            ->where('legacy_group_key', $legacyKey)
            ->where('key', '!=', $groupKey)
            ->exists();

        return $claimed ? null : $legacyKey;
    }

    /**
     * @param  list<array<string, mixed>>  $acfFields
     * @param  array<string, int>  $sortCounters
     * @param  list<int>  $touchedFieldIds
     */
    private function importFieldTree(
        array $acfFields,
        FieldGroup $group,
        string $entity,
        string $legacyPostType,
        array &$sortCounters,
        string $groupKey,
        string $keyPrefix = '',
        ?int $parentFieldId = 0,
        array &$touchedFieldIds = [],
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
                    $legacyPostType,
                    $sortCounters,
                    $groupKey,
                    $keyPrefix,
                    $parentFieldId,
                    $touchedFieldIds,
                );

                continue;
            }

            if ($type === 'repeater' && $name !== '' && is_array($acfField['sub_fields'] ?? null)) {
                $fieldKey = $this->normalizeFieldKey($keyPrefix !== '' ? $keyPrefix.$name : $name);

                if ($this->shouldSkipField($fieldKey, $entity) || ! $this->validFieldKey($fieldKey)) {
                    continue;
                }

                $sortCounters[$groupKey] = ($sortCounters[$groupKey] ?? 0) + 1;
                $scopedParentId = $parentFieldId ?? 0;

                $parent = Field::query()->updateOrCreate(
                    [
                        'field_group_id' => $group->id,
                        'key' => $fieldKey,
                        'legacy_post_type' => $legacyPostType,
                        'parent_field_id' => $scopedParentId,
                    ],
                    [
                        'entity' => $entity,
                        'legacy_post_type' => $legacyPostType,
                        'name' => (string) ($acfField['label'] ?? $name),
                        'type' => FieldTypes::REPEATER,
                        'storage' => 'field_value',
                        'config' => array_filter([
                            'legacy_field_key' => $acfField['key'] ?? null,
                            'legacy_acf_type' => 'repeater',
                        ]),
                        'sort_order' => $sortCounters[$groupKey],
                        'is_filterable' => false,
                        'status' => 'active',
                        'legacy_field_key' => $acfField['key'] ?? null,
                    ],
                );

                $touchedFieldIds[] = $parent->id;
                $imported++;
                $imported += $this->importFieldTree(
                    $acfField['sub_fields'],
                    $group,
                    $entity,
                    $legacyPostType,
                    $sortCounters,
                    $groupKey,
                    '',
                    $parent->id,
                    $touchedFieldIds,
                );

                continue;
            }

            if ($name === '') {
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
            $config['widget_eligible'] = WidgetFieldEligibility::fromAcfFlag(
                $acfField['fl-react-app-column-default'] ?? 0,
            );

            $scopedParentId = $parentFieldId ?? 0;

            $existing = Field::query()
                ->where('field_group_id', $group->id)
                ->where('key', $fieldKey)
                ->where('parent_field_id', $scopedParentId)
                ->where(function ($query) use ($legacyPostType): void {
                    $query->where('legacy_post_type', $legacyPostType)
                        ->orWhere('legacy_post_type', '');
                })
                ->first();

            if ($existing !== null) {
                $config = $this->isSystemField($entity, $fieldKey)
                    ? $this->mergeSystemFieldConfig($existing, $config)
                    : $this->mergeImportedFieldConfig($existing, $config);
            }

            if ($existing !== null && $existing->legacy_post_type === '' && $legacyPostType !== '') {
                Field::query()
                    ->where('field_group_id', $group->id)
                    ->where('key', $fieldKey)
                    ->where('parent_field_id', $scopedParentId)
                    ->where('legacy_post_type', '')
                    ->update(['legacy_post_type' => $legacyPostType]);
            }

            $field = Field::query()->updateOrCreate(
                [
                    'field_group_id' => $group->id,
                    'key' => $fieldKey,
                    'legacy_post_type' => $legacyPostType,
                    'parent_field_id' => $scopedParentId,
                ],
                [
                    'entity' => $entity,
                    'legacy_post_type' => $legacyPostType,
                    'parent_field_id' => $scopedParentId,
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

            $touchedFieldIds[] = $field->id;
            $imported++;
        }

        return $imported;
    }

    /**
     * @param  array<string, true>  $seenGroupKeys
     * @param  list<int>  $touchedFieldIds
     */
    private function deactivateStaleImportedFields(array $seenGroupKeys, array $touchedFieldIds): void
    {
        if ($seenGroupKeys === []) {
            return;
        }

        $groupIds = FieldGroup::query()
            ->whereIn('key', array_keys($seenGroupKeys))
            ->pluck('id');

        if ($groupIds->isEmpty()) {
            return;
        }

        Field::query()
            ->whereIn('field_group_id', $groupIds)
            ->where('status', 'active')
            ->whereNotIn('id', $touchedFieldIds)
            ->whereNotIn('key', $this->protectedFieldKeys())
            ->update(['status' => 'inactive']);
    }

    /**
     * @return list<string>
     */
    private function protectedFieldKeys(): array
    {
        $keys = ['internal_margin_notes'];

        /** @var array<string, array<string, mixed>> $defaults */
        $defaults = config('fil-fields.defaults', []);

        foreach ($defaults as $entityDefaults) {
            $keys = array_merge($keys, array_keys($entityDefaults));
        }

        return array_values(array_unique($keys));
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
        return $this->mergeImportedFieldConfig($existing, $incoming, preserveChoices: true);
    }

    /**
     * @param  array<string, mixed>  $incoming
     * @return array<string, mixed>
     */
    private function mergeImportedFieldConfig(Field $existing, array $incoming, bool $preserveChoices = false): array
    {
        $current = is_array($existing->config) ? $existing->config : [];

        if ($preserveChoices && ($current['choices'] ?? []) !== []) {
            unset($incoming['choices']);
        }

        if (($current['widget_eligible_admin'] ?? false) === true) {
            unset($incoming['widget_eligible']);
        }

        return array_replace($current, array_filter($incoming, static fn ($value): bool => $value !== null));
    }
}
