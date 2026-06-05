<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\Lead;
use App\Models\User;

final class ProspectPortalAccessService
{
    public function canAccessPortal(User $user): bool
    {
        $staffPermission = (string) config('fil.staff_login_permission', 'app.access');

        if ($user->hasPermissionTo($staffPermission, 'web')) {
            return false;
        }

        if (! $user->hasRole('prospect', 'web')) {
            return false;
        }

        return $this->hasActiveApplication($user);
    }

    public function hasActiveApplication(User $user): bool
    {
        return Lead::query()
            ->where('prospect_user_id', $user->id)
            ->where('pipeline_phase', '!=', 99)
            ->exists();
    }
}
