<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use App\Services\Portal\ProspectPortalAccessService;

final class ProspectPolicy
{
    public function __construct(
        private readonly ProspectPortalAccessService $portalAccess,
    ) {}

    public function accessProspectPortal(User $user): bool
    {
        return $this->portalAccess->canAccessPortal($user);
    }
}
