<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Lead;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class LeadPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->scope->mayListLeads($user);
    }

    public function view(User $user, Lead $lead): bool
    {
        return $this->scope->canViewLead($user, $lead);
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'leads.manage') && $this->scope->mayListLeads($user);
    }

    public function update(User $user, Lead $lead): bool
    {
        return $this->create($user) && $this->view($user, $lead);
    }

    public function delete(User $user, Lead $lead): bool
    {
        return $this->create($user) && $this->view($user, $lead);
    }
}
