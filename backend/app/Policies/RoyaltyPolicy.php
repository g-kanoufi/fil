<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\RoyaltyPeriod;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class RoyaltyPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'royalties.view');
    }

    public function view(User $user, RoyaltyPeriod $period): bool
    {
        return $this->scope->canViewRoyaltyPeriod($user, $period);
    }

    public function calculate(User $user): bool
    {
        return $this->allows($user, 'royalties.manage');
    }

    public function manage(User $user): bool
    {
        return $this->allows($user, 'royalties.manage');
    }
}
