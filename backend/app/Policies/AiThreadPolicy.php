<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AiThread;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class AiThreadPolicy
{
    use ChecksFilPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'ai.use');
    }

    public function view(User $user, AiThread $thread): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        return $thread->user_id === $user->id || $user->hasRole('admin');
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function sendMessage(User $user, AiThread $thread): bool
    {
        return $this->view($user, $thread);
    }
}
