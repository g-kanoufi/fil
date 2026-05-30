<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class StaffPolicy
{
    use ChecksFilPermissions;

    public function accessStaffApp(User $user): bool
    {
        return $this->allows($user, (string) config('fil.staff_login_permission', 'app.access'));
    }
}
