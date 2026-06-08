<?php

declare(strict_types=1);

namespace App\Support\Fields;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Legacy\LegacyPostTypeEntityMap;

final class FieldGroupPostTypeResolver
{
    /**
     * @return list<string>
     */
    public static function groupKeysForEntity(string $entity): array
    {
        /** @var array<string, list<string>> $map */
        $map = config('fil-fields.entity_group_keys', self::defaultEntityGroupKeys());

        return $map[$entity] ?? [];
    }

    /**
     * Groups shown on Settings → Custom fields (excludes private/admin note repeaters).
     *
     * @return list<string>
     */
    public static function adminGroupKeysForEntity(string $entity): array
    {
        /** @var array<string, list<string>> $map */
        $map = config('fil-fields.admin_group_keys', self::defaultAdminGroupKeys());

        return $map[$entity] ?? [];
    }

    public static function resolve(FieldGroup $group): ?string
    {
        /** @var array<string, array<string, mixed>> $configured */
        $configured = config('fil-legacy-acf.groups', []);

        $legacyKey = (string) ($group->legacy_group_key ?? '');

        if ($legacyKey !== '' && isset($configured[$legacyKey])) {
            $groupConfig = $configured[$legacyKey];
            $variants = $groupConfig['post_type_variants'] ?? null;

            if (is_array($variants)) {
                foreach ($variants as $postType => $variant) {
                    if (! is_array($variant)) {
                        continue;
                    }

                    $variantKey = (string) ($variant['key'] ?? '');

                    if ($variantKey !== '' && $variantKey === $group->key) {
                        return (string) $postType;
                    }
                }
            }

            if (isset($groupConfig['legacy_post_type']) && is_string($groupConfig['legacy_post_type'])) {
                return $groupConfig['legacy_post_type'];
            }
        }

        /** @var array<string, string> $byGroupKey */
        $byGroupKey = [
            'applications' => 'application',
            'applications-advanced' => 'application',
            'units' => 'store',
            'units-client-fields' => 'store',
            'locations' => 'franchise_location',
            'areas' => 'area',
            'organizations' => 'organization',
            'user' => 'user',
            'user-client-fields' => 'user',
            'private-notes-application' => 'application',
            'private-notes-store' => 'store',
            'private-notes-location' => 'franchise_location',
            'admin-notes-application' => 'application',
            'admin-notes-store' => 'store',
            'admin-notes-location' => 'franchise_location',
            'private-notes' => self::inferNotePostType($group, 'application'),
            'administrative-notes' => self::inferNotePostType($group, 'application'),
        ];

        return $byGroupKey[$group->key] ?? null;
    }

    public static function resolveForEntity(FieldGroup $group, string $entity): ?string
    {
        $postType = self::resolve($group);

        if ($postType === null) {
            return LegacyPostTypeEntityMap::defaultPostTypeForEntity($entity);
        }

        if (LegacyPostTypeEntityMap::entityFor($postType) !== $entity) {
            return null;
        }

        return $postType;
    }

    /**
     * @return array<string, list<string>>
     */
    private static function defaultAdminGroupKeys(): array
    {
        return [
            'lead' => ['applications', 'applications-advanced'],
            'store' => ['units', 'units-client-fields', 'locations'],
            'contact' => ['user', 'user-client-fields'],
            'area' => ['areas'],
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    private static function defaultEntityGroupKeys(): array
    {
        return [
            'lead' => [
                'applications',
                'applications-advanced',
                'private-notes',
                'private-notes-application',
                'administrative-notes',
                'admin-notes-application',
            ],
            'store' => [
                'units',
                'units-client-fields',
                'locations',
                'private-notes-store',
                'private-notes-location',
                'admin-notes-store',
                'admin-notes-location',
            ],
            'contact' => ['user', 'user-client-fields'],
            'area' => ['areas'],
            'organization' => ['organizations'],
        ];
    }

    private static function inferNotePostType(FieldGroup $group, string $default): ?string
    {
        $entity = Field::query()
            ->where('field_group_id', $group->id)
            ->value('entity');

        return match ($entity) {
            'lead' => 'application',
            'store' => 'store',
            'contact' => 'user',
            default => $default,
        };
    }
}
