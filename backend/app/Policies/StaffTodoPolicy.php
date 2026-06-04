<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\StaffTodo;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;

final class StaffTodoPolicy
{
    use ChecksFilPermissions;

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'todos.view');
    }

    public function view(User $user, StaffTodo $todo): bool
    {
        return $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'todos.manage');
    }

    public function update(User $user, StaffTodo $todo): bool
    {
        return $this->create($user);
    }

    public function delete(User $user, StaffTodo $todo): bool
    {
        return $this->create($user);
    }
}
