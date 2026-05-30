<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Gate;

final class UserAccessService
{
    public function primaryRole(User $user): ?string
    {
        $priority = ['admin', 'franchisor', 'area_rep', 'lead_owner', 'franchisee', 'storemanager', 'employee', 'prospect'];

        foreach ($priority as $role) {
            if ($user->hasRole($role)) {
                return $role;
            }
        }

        return $user->getRoleNames()->first();
    }

    public function canAccessStaffApp(User $user): bool
    {
        return Gate::forUser($user)->allows('accessStaffApp');
    }

    /**
     * @return list<string>
     */
    public function permissions(User $user): array
    {
        return $user->getAllPermissions()->pluck('name')->sort()->values()->all();
    }
}
