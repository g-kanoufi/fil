<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Area;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class AreaPolicy
{
    use ChecksFilPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'stores.view');
    }

    public function view(User $user, Area $area): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'stores.manage');
    }

    public function update(User $user, Area $area): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, Area $area): bool
    {
        return $this->create($user);
    }
}
