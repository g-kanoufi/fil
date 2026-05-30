<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Fdd;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class FddPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'fdd.view');
    }

    public function view(User $user, Fdd $fdd): bool
    {
        return $this->scope->canViewFdd($user, $fdd);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'fdd.manage');
    }

    public function update(User $user, Fdd $fdd): bool
    {
        return $this->create($user);
    }

    public function send(User $user, Fdd $fdd): bool
    {
        return $this->create($user);
    }
}
