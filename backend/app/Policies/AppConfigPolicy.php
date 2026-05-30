<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\AppConfig;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class AppConfigPolicy
{
    use ChecksFilPermissions;

    public function view(User $user, AppConfig $config = new AppConfig): bool
    {
        return $this->allows($user, 'app.access');
    }
}
