<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class CommunicationPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'communications.view');
    }

    public function view(User $user, Communication $communication): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($communication->lead_id === null) {
            return $this->scope->isUnrestricted($user);
        }

        $lead = $communication->lead;

        return $lead instanceof Lead && $this->scope->canViewLead($user, $lead);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'communications.manage');
    }
}
