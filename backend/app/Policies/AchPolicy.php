<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\AchTransfer;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class AchPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'ach.view');
    }

    public function view(User $user, AchTransfer $transfer): bool
    {
        return $this->scope->canViewAchTransfer($user, $transfer);
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'ach.manage');
    }
}
