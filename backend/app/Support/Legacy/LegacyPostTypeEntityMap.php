<?php

declare(strict_types=1);

namespace App\Support\Legacy;

final class LegacyPostTypeEntityMap
{
    public static function entityFor(string $legacyPostType): string
    {
        /** @var array<string, string> $map */
        $map = config('fil-legacy-acf.post_type_entity', []);

        return $map[$legacyPostType] ?? 'lead';
    }

    /**
     * Default legacy post type when analyzing a FIL entity (use --post-type= for store location fields).
     *
     * @return array<string, string>
     */
    public static function defaultEntityPostTypes(): array
    {
        return [
            'lead' => 'application',
            'store' => 'store',
            'area' => 'area',
            'organization' => 'organization',
            'contact' => 'user',
        ];
    }

    public static function defaultPostTypeForEntity(string $entity): ?string
    {
        return self::defaultEntityPostTypes()[$entity] ?? null;
    }

    public static function assertEntityMatchesPostType(string $entity, string $legacyPostType): void
    {
        $expected = self::entityFor($legacyPostType);

        if ($expected !== $entity) {
            throw new \InvalidArgumentException(
                "Entity {$entity} does not match legacy post type {$legacyPostType} (expected {$expected}).",
            );
        }
    }
}
