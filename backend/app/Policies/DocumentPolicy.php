<?php

declare(strict_types=1);

namespace App\Policies;

use App\Domain\Document as DocumentArea;
use App\Models\Document;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Auth\ResourceScopeService;

final class DocumentPolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    public function viewAny(User $user): bool
    {
        return $this->allows($user, 'documents.view');
    }

    public function view(User $user, Document|DocumentArea $document): bool
    {
        if ($document instanceof DocumentArea) {
            return $this->viewAny($user);
        }

        if ($document->relationLoaded('links') === false) {
            $document->load('links.linkable');
        }

        return $this->scope->canViewDocument($user, $document);
    }
}
