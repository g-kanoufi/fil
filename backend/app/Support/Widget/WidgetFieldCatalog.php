<?php

declare(strict_types=1);

namespace App\Support\Widget;

use App\Models\Field;
use App\Models\FieldGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;

/**
 * Which custom field groups may appear on embeddable widget forms.
 *
 * Application fields (entity lead) plus User-group profile fields (entity contact).
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
        if (! in_array($field->field_group_id, self::allowedGroupIds(), true)) {
            return false;
        }

        $entityAllowed = match ($field->entity) {
            'lead' => true,
            'contact' => in_array($field->field_group_id, self::userGroupIds(), true),
            default => false,
        };

        if (! $entityAllowed) {
            return false;
        }

        return WidgetFieldEligibility::isEligible($field);
    }

    /**
     * @param  Builder<Field>|Relation  $query
     */
    public static function applyWidgetFieldScope(Builder|Relation $query): void
    {
        $allowedIds = self::allowedGroupIds();
        $userGroupIds = self::userGroupIds();

        $query->whereIn('field_group_id', $allowedIds)
            ->where(function (Builder $scoped) use ($userGroupIds): void {
                $scoped->where('entity', 'lead');

                if ($userGroupIds !== []) {
                    $scoped->orWhere(function (Builder $contactScoped) use ($userGroupIds): void {
                        $contactScoped->where('entity', 'contact')
                            ->whereIn('field_group_id', $userGroupIds);
                    });
                }
            });

        WidgetFieldEligibility::applyEligibleScope($query);
    }

    /**
     * @return list<int>
     */
    private static function userGroupIds(): array
    {
        if (! in_array('user', self::allowedGroupKeys(), true)) {
            return [];
        }

        return FieldGroup::query()
            ->where('key', 'user')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }
}
