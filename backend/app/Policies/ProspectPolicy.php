<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class ProspectPolicy
{
    use ChecksFilPermissions;

    public function accessProspectPortal(User $user): bool
    {
        if ($this->allows($user, (string) config('fil.staff_login_permission', 'app.access'))) {
            return false;
        }

        if (! $user->hasRole('prospect')) {
            return false;
        }

        return Lead::query()
            ->where('prospect_user_id', $user->id)
            ->where('pipeline_phase', '!=', 99)
            ->exists();
    }
}
