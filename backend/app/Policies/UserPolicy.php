<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;

final class UserPolicy
{
    public function viewSelf(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }

    public function updateSelf(User $user, User $model): bool
    {
        return $user->id === $model->id;
    }
}
