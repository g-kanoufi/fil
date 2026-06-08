<?php

declare(strict_types=1);

namespace App\Support\Fields;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Legacy ACF note repeaters — stored in {@see \App\Models\EntityNote}, not field_values.
 */
final class NoteFieldCatalog
{
    /**
     * @return list<string>
     */
    public static function excludedGroupKeys(): array
    {
        /** @var list<string> $keys */
        $keys = config('fil-fields.note_field_group_keys', []);

        return $keys !== [] ? $keys : [
            'private-notes',
            'private-notes-application',
            'private-notes-store',
            'private-notes-location',
            'administrative-notes',
            'admin-notes-application',
            'admin-notes-store',
            'admin-notes-location',
        ];
    }

    /**
     * @return list<string>
     */
    public static function excludedFieldKeys(): array
    {
        /** @var list<string> $keys */
        $keys = config('fil-fields.note_field_keys', []);

        return $keys !== [] ? $keys : [
            'private_notes',
            'administrative_notes',
        ];
    }

    public static function isNoteGroupKey(string $groupKey): bool
    {
        return in_array($groupKey, self::excludedGroupKeys(), true);
    }

    public static function isNoteFieldKey(string $fieldKey): bool
    {
        return in_array($fieldKey, self::excludedFieldKeys(), true);
    }

    /**
     * @param  Builder<\App\Models\FieldGroup>|Relation  $query
     */
    public static function applyExcludedGroupScope(Builder|Relation $query): void
    {
        $query->whereNotIn('key', self::excludedGroupKeys());
    }

    /**
     * @param  Builder<\App\Models\Field>|Relation  $query
     */
    public static function applyExcludedFieldScope(Builder|Relation $query): void
    {
        $query->whereNotIn('key', self::excludedFieldKeys());
    }
}
