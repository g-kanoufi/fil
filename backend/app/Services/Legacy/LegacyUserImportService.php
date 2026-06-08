<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\User;
use App\Support\Legacy\LegacyTableNames;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

final class LegacyUserImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
        private readonly LegacyUserProfileResolver $profileResolver,
        private readonly LegacyUserRoleMapper $roleMapper,
    ) {}

    /**
     * @return array{users: int, staff: int, prospects: int, skipped: int, roles_assigned: int}
     */
    public function import(string $dumpPath, string $sitePrefix, bool $execute, bool $relevantOnly = true): array
    {
        $this->ensureRolesExist();

        $profiles = $this->profileResolver->resolve($dumpPath, $sitePrefix);
        $table = LegacyTableNames::fromSitePrefix($sitePrefix)->networkTable('users');
        $stats = [
            'users' => 0,
            'staff' => 0,
            'prospects' => 0,
            'skipped' => 0,
            'roles_assigned' => 0,
        ];

        $result = $this->importer->import(
            $dumpPath,
            $table,
            function (array $row, bool $execute) use (&$stats, $profiles, $relevantOnly): void {
                $legacyId = (int) ($row['ID'] ?? 0);
                $email = trim((string) ($row['user_email'] ?? ''));
                $spam = (int) ($row['spam'] ?? 0);
                $deleted = (int) ($row['deleted'] ?? 0);

                if ($legacyId <= 0 || $email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $stats['skipped']++;

                    return;
                }

                if ($spam === 1 || $deleted === 1) {
                    $stats['skipped']++;

                    return;
                }

                $profile = array_merge(
                    ['wp_roles' => [], 'first_name' => null, 'last_name' => null, 'phone' => null],
                    $profiles[$legacyId] ?? [],
                );
                $wpRoles = $profile['wp_roles'];
                $isStaff = $this->roleMapper->isRelevantStaff($wpRoles);
                $isProspectOnly = $wpRoles === [] || $this->roleMapper->isProspectOnly($wpRoles);

                if ($relevantOnly && $wpRoles !== [] && $this->roleMapper->isProspectOnly($wpRoles)) {
                    $stats['skipped']++;

                    return;
                }

                $stats['users']++;
                $isStaff ? $stats['staff']++ : $stats['prospects']++;

                if (! $execute) {
                    return;
                }

                $displayName = trim((string) ($row['display_name'] ?? ''));
                $firstName = $profile['first_name'];
                $lastName = $profile['last_name'];

                if ($firstName === null && $displayName !== '') {
                    $parts = preg_split('/\s+/', $displayName, 2) ?: [];
                    $firstName = $parts[0] ?? null;
                    $lastName = $lastName ?? ($parts[1] ?? null);
                }

                $user = User::query()->where('legacy_user_id', $legacyId)->first()
                    ?? User::query()->where('email', $email)->first();

                $attributes = [
                    'legacy_user_id' => $legacyId,
                    'email' => $email,
                    'name' => $displayName !== '' ? $displayName : $email,
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'phone' => $profile['phone'],
                ];

                if ($user === null) {
                    $user = User::query()->create([
                        ...$attributes,
                        'password' => Str::password(32),
                    ]);
                } else {
                    $user->fill($attributes);

                    if ($user->password === null || $user->password === '') {
                        $user->password = Str::password(32);
                    }

                    $user->save();
                }

                $this->assignRoles($user, $wpRoles);
                $stats['roles_assigned']++;
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

        return $stats;
    }

    /**
     * @return array{profiles_with_roles: int, matched: int, staff: int, prospects: int, skipped: int}
     */
    public function syncRoles(string $dumpPath, string $sitePrefix, bool $execute): array
    {
        $this->ensureRolesExist();

        $profiles = $this->profileResolver->resolve($dumpPath, $sitePrefix);
        $stats = [
            'profiles_with_roles' => 0,
            'matched' => 0,
            'staff' => 0,
            'prospects' => 0,
            'skipped' => 0,
        ];

        foreach ($profiles as $profile) {
            if (($profile['wp_roles'] ?? []) !== []) {
                $stats['profiles_with_roles']++;
            }
        }

        User::query()
            ->whereNotNull('legacy_user_id')
            ->orderBy('id')
            ->each(function (User $user) use ($profiles, $execute, &$stats): void {
                $legacyId = (int) $user->legacy_user_id;
                $profile = $profiles[$legacyId] ?? null;

                if ($profile === null) {
                    $stats['skipped']++;

                    return;
                }

                $stats['matched']++;
                $wpRoles = $profile['wp_roles'];
                $isStaff = $this->roleMapper->isRelevantStaff($wpRoles);
                $isStaff ? $stats['staff']++ : $stats['prospects']++;

                if (! $execute) {
                    return;
                }

                $this->assignRoles($user, $wpRoles);
            });

        return $stats;
    }

    /**
     * @param  list<string>  $wpRoles
     */
    private function assignRoles(User $user, array $wpRoles): void
    {
        $user->syncRoles($this->roleMapper->mapRoles($wpRoles));
    }

    private function ensureRolesExist(): void
    {
        foreach (array_keys(config('fil.role_permissions', [])) as $roleName) {
            Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        }
    }
}
