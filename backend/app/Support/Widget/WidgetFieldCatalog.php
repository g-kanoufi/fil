<?php

declare(strict_types=1);

namespace App\Support\Widget;

use App\Models\Field;
use App\Models\FieldGroup;

/**
 * Which custom field groups may appear on embeddable widget forms.
 */
final class WidgetFieldCatalog
{
    /**
     * @return list<string>
     */
    public static function allowedGroupKeys(): array
    {
        /** @var list<string> $keys */
        $keys = config('fil.widget.allowed_field_group_keys', ['applications', 'user']);

        return array_values(array_filter($keys, fn (string $key): bool => $key !== ''));
    }

    /**
     * @return list<int>
     */
    public static function allowedGroupIds(): array
    {
        $keys = self::allowedGroupKeys();

        if ($keys === []) {
            return [];
        }

        return FieldGroup::query()
            ->whereIn('key', $keys)
            ->pluck('id')
            ->all();
    }

    public static function fieldIsAllowed(Field $field): bool
    {
        return in_array($field->field_group_id, self::allowedGroupIds(), true);
    }
}
