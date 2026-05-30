<?php

declare(strict_types=1);

namespace App\Actions\Fields;

use App\Models\Field;

final class UpdateField
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function handle(Field $field, array $attributes): Field
    {
        $field->fill(array_filter(
            [
                'field_group_id' => $attributes['field_group_id'] ?? null,
                'name' => $attributes['name'] ?? null,
                'type' => $attributes['type'] ?? null,
                'status' => $attributes['status'] ?? null,
            ],
            static fn ($value): bool => $value !== null,
        ));

        if (array_key_exists('config', $attributes)) {
            $field->config = $attributes['config'];
        }

        foreach (['required', 'is_filterable', 'is_sortable', 'is_facetable'] as $flag) {
            if (array_key_exists($flag, $attributes)) {
                $field->{$flag} = (bool) $attributes[$flag];
            }
        }

        $field->save();

        return $field->refresh();
    }
}
