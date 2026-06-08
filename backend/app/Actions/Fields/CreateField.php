<?php

declare(strict_types=1);

namespace App\Actions\Fields;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Fields\FieldConfigNormalizer;
use App\Support\Fields\FieldGroupPostTypeResolver;

final class CreateField
{
    /**
     * Admin-created fields always store in `field_values` (or relation links) —
     * we never run runtime DDL to add columns. See docs/METADATA.md.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function handle(array $attributes): Field
    {
        $nextSort = (int) Field::query()
            ->where('field_group_id', $attributes['field_group_id'])
            ->max('sort_order');

        $group = FieldGroup::query()->findOrFail($attributes['field_group_id']);
        $legacyPostType = FieldGroupPostTypeResolver::resolveForEntity($group, $attributes['entity']) ?? '';

        return Field::query()->create([
            'field_group_id' => $attributes['field_group_id'],
            'entity' => $attributes['entity'],
            'legacy_post_type' => $legacyPostType,
            'key' => $attributes['key'],
            'name' => $attributes['name'],
            'type' => $attributes['type'],
            'storage' => 'field_value',
            'maps_to_column' => null,
            'config' => FieldConfigNormalizer::normalize(
                is_array($attributes['config'] ?? null) ? $attributes['config'] : null,
            ),
            'required' => (bool) ($attributes['required'] ?? false),
            'is_filterable' => (bool) ($attributes['is_filterable'] ?? false),
            'is_sortable' => (bool) ($attributes['is_sortable'] ?? false),
            'is_facetable' => (bool) ($attributes['is_facetable'] ?? false),
            'sort_order' => $nextSort + 1,
            'status' => 'active',
        ]);
    }
}
