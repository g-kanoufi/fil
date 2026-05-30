<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Settings;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class SettingPolicy
{
    use ChecksFilPermissions;

    public function manage(User $user, Settings $settings = new Settings): bool
    {
        return $this->allows($user, 'settings.manage');
    }
}
