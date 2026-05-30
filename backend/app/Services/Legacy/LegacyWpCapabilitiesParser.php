<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyWpCapabilitiesParser
{
    /**
     * Extract role slugs from a legacy serialized capabilities string.
     *
     * @return list<string>
     */
    public static function parseRoles(?string $serialized): array
    {
        if ($serialized === null || $serialized === '' || $serialized === 'a:0:{}') {
            return [];
        }

        $normalized = stripcslashes($serialized);

        preg_match_all('/s:\d+:"([^"]+)";b:1;/', $normalized, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
