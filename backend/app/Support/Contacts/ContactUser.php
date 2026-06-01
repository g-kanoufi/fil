<?php

declare(strict_types=1);

namespace App\Support\Contacts;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * CRM contacts are {@see User} records with staff/franchise roles — not prospects.
 *
 * Prospects remain users (for leads, FDD, comms) but are excluded from the contacts grid/API.
 */
final class ContactUser
{
    /**
     * @return list<string>
     */
    public static function prospectRoles(): array
    {
        /** @var list<string> $roles */
        $roles = config('fil.contact.prospect_roles', ['prospect']);

        return $roles;
    }

    public static function isContactRecord(User $user): bool
    {
        $roles = $user->getRoleNames();

        if ($roles->isEmpty()) {
            return false;
        }

        return $roles->contains(
            fn (string $role): bool => ! in_array($role, self::prospectRoles(), true),
        );
    }

    /**
     * @param  Builder<User>  $query
     */
    public static function applyContactScope(Builder $query): void
    {
        $prospectRoles = self::prospectRoles();

        $query->whereHas(
            'roles',
            fn (Builder $roleQuery) => $roleQuery->whereNotIn('name', $prospectRoles),
        );
    }
}
