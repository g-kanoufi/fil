<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Closing;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class ClosingPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'leads.view');
    }

    public function view(User $user, Closing $closing): bool
    {
        return $this->scope->canViewClosing($user, $closing);
    }
}
