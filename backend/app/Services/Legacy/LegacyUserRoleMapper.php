<?php

declare(strict_types=1);

namespace App\Services\Legacy;

final class LegacyUserRoleMapper
{
    /**
     * @param  list<string>  $wpRoles
     * @return list<string> FIL role names (unique, ordered by privilege)
     */
    public function mapRoles(array $wpRoles): array
    {
        /** @var array<string, string> $map */
        $map = config('fil.legacy.role_map', []);
        $filRoles = [];

        foreach ($wpRoles as $wpRole) {
            $normalized = strtolower(trim($wpRole));
            $filRole = $map[$wpRole] ?? $map[$normalized] ?? null;

            if ($filRole !== null && ! in_array($filRole, $filRoles, true)) {
                $filRoles[] = $filRole;
            }
        }

        if ($filRoles === []) {
            return ['prospect'];
        }

        $priority = ['admin', 'franchisor', 'lead_owner', 'prospect'];
        usort($filRoles, static function (string $a, string $b) use ($priority): int {
            return array_search($a, $priority, true) <=> array_search($b, $priority, true);
        });

        return $filRoles;
    }

    /**
     * @param  list<string>  $wpRoles
     */
    public function isRelevantStaff(array $wpRoles): bool
    {
        $filRoles = $this->mapRoles($wpRoles);

        return count(array_diff($filRoles, ['prospect'])) > 0;
    }

    /**
     * @param  list<string>  $wpRoles
     */
    public function isProspectOnly(array $wpRoles): bool
    {
        $filRoles = $this->mapRoles($wpRoles);

        return $filRoles === ['prospect'];
    }
}
