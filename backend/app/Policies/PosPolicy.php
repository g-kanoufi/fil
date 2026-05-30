<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\PosConnection;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class PosPolicy
{
    use ChecksFilPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'stores.view');
    }

    public function view(User $user, PosConnection $connection): bool
    {
        return $this->viewAny($user);
    }

    public function sync(User $user): bool
    {
        return $this->allows($user, 'stores.manage');
    }
}
