<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Fields\FieldGroupPostTypeResolver;

final class LegacyFieldPostTypeBackfillService
{
    /**
     * @return array{groups: int, fields: int, skipped_groups: list<string>}
     */
    public function backfill(bool $execute = true): array
    {
        $groupsUpdated = 0;
        $fieldsUpdated = 0;
        /** @var list<string> $skippedGroups */
        $skippedGroups = [];

        foreach (FieldGroup::query()->orderBy('id')->get() as $group) {
            $legacyPostType = FieldGroupPostTypeResolver::resolve($group);

            if ($legacyPostType === null) {
                $skippedGroups[] = $group->key;

                continue;
            }

            $groupsUpdated++;

            if (! $execute) {
                $fieldsUpdated += Field::query()
                    ->where('field_group_id', $group->id)
                    ->where('legacy_post_type', '!=', $legacyPostType)
                    ->count();

                continue;
            }

            $fieldsUpdated += Field::query()
                ->where('field_group_id', $group->id)
                ->where('legacy_post_type', '!=', $legacyPostType)
                ->update(['legacy_post_type' => $legacyPostType]);
        }

        return [
            'groups' => $groupsUpdated,
            'fields' => $fieldsUpdated,
            'skipped_groups' => $skippedGroups,
        ];
    }
}
