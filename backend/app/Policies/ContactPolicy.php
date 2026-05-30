<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Contact;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class ContactPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'contacts.view');
    }

    public function view(User $user, Contact|User $contact): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($contact instanceof User) {
            return $this->scope->canViewContact($user, $contact);
        }

        return true;
    }

    public function create(User $user): bool
    {
        return $this->allows($user, 'contacts.manage');
    }

    public function update(User $user, Contact|User $contact): bool
    {
        if (! $this->create($user)) {
            return false;
        }

        return $this->view($user, $contact);
    }
}
