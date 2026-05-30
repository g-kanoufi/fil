<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Store;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class StorePolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'stores.view');
    }

    public function view(User $user, Store $store): bool
    {
        return $this->scope->canViewStore($user, $store);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'stores.manage');
    }

    public function update(User $user, Store $store): bool
    {
        return $this->create($user) && $this->view($user, $store);
    }

    public function delete(User $user, Store $store): bool
    {
        return $this->create($user) && $this->view($user, $store);
    }
}
