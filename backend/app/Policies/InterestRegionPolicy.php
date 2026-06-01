<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\InterestRegion;
use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class InterestRegionPolicy
{
    use ChecksFilPermissions;

    public function viewAny(User $user): bool
    {
        return $user->can('viewAny', Lead::class);
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'settings.manage');
    }

    public function create(User $user): bool
    {
        return $this->manage($user);
    }

    public function update(User $user, InterestRegion $interestRegion): bool
    {
        return $this->manage($user);
    }

    public function delete(User $user, InterestRegion $interestRegion): bool
    {
        return $this->manage($user);
    }
}
