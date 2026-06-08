<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Fields\FieldChoiceSet;
use App\Support\Fields\FieldGroupPostTypeResolver;

/**
 * Ensures tier-1 system fields exist with enriched choice catalogs.
 * Never overwrites admin-edited labels; only fills empty choices or merges metadata.
 */
final class SystemFieldService
{
    public function ensureSystemFields(): void
    {
        /** @var array<string, array<string, array<string, mixed>>> $defaults */
        $defaults = config('fil-fields.defaults', []);

        /** @var array<string, string> $groupKeys */
        $groupKeys = config('fil-fields.group_keys', [
            'lead' => 'applications',
            'store' => 'units',
        ]);

        /** @var array<string, array<string, array<string, mixed>>> $systemKeys */
        $systemKeys = config('fil-fields.system_keys', []);

        foreach ($defaults as $entity => $fieldDefaults) {
            $groupKey = $groupKeys[$entity] ?? null;

            if ($groupKey === null) {
                continue;
            }

            $group = FieldGroup::query()->where('key', $groupKey)->first();

            if ($group === null) {
                continue;
            }

            foreach ($fieldDefaults as $key => $definition) {
                $this->ensureField($group, $entity, $key, $definition, $systemKeys[$entity][$key] ?? []);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $systemMeta
     */
    private function ensureField(
        FieldGroup $group,
        string $entity,
        string $key,
        array $definition,
        array $systemMeta,
    ): void {
        $field = Field::query()->firstOrCreate(
            [
                'field_group_id' => $group->id,
                'key' => $key,
            ],
            [
                'entity' => $entity,
                'legacy_post_type' => FieldGroupPostTypeResolver::resolveForEntity($group, $entity) ?? '',
                'name' => (string) ($definition['name'] ?? $key),
                'type' => (string) ($definition['type'] ?? 'select'),
                'storage' => 'column',
                'maps_to_column' => $key,
                'sort_order' => 1,
                'required' => false,
                'is_filterable' => (bool) ($systemMeta['filterable'] ?? false),
                'status' => 'active',
            ],
        );

        $wasRecentlyCreated = $field->wasRecentlyCreated;
        $config = is_array($field->config) ? $field->config : [];
        $existing = new FieldChoiceSet($config['choices'] ?? null);

        /** @var list<array<string, mixed>> $defaultChoices */
        $defaultChoices = $definition['choices'] ?? [];

        if ($existing->isEmpty()) {
            $config['choices'] = $defaultChoices;
        } else {
            $config['choices'] = $existing->enrichFromDefaults($defaultChoices);
        }

        $config['system'] = true;
        $config['role'] = $systemMeta['role'] ?? ($config['role'] ?? null);

        $updates = [
            'config' => $config,
            'status' => 'active',
            'storage' => 'column',
            'maps_to_column' => $key,
            'is_filterable' => (bool) ($systemMeta['filterable'] ?? $field->is_filterable),
        ];

        if ($wasRecentlyCreated || $field->type === 'text') {
            $updates['type'] = (string) ($definition['type'] ?? $field->type);
        }

        if ($wasRecentlyCreated) {
            $updates['name'] = (string) ($definition['name'] ?? $field->name);
        }

        $field->fill($updates)->save();
    }
}
