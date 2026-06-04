<?php

declare(strict_types=1);

namespace App\Services\Operations;

use App\Models\EntityNote;
use App\Models\User;
use App\Services\Auth\UiAccessService;
use Illuminate\Support\Collection;

final class EntityNoteAccessService
{
    public function __construct(
        private readonly UiAccessService $uiAccess,
    ) {}

    public function canViewNotes(User $user, string $entityKey): bool
    {
        if ($entityKey === 'closing') {
            return true;
        }

        $grants = $this->uiAccess->notesAccessFor($user);
        $legacyKey = $this->uiAccess->noteEntityLegacyKey($entityKey);

        return (bool) ($grants[$legacyKey]['notes'] ?? false);
    }

    public function canViewPrivateNotes(User $user, string $entityKey): bool
    {
        if ($entityKey === 'closing') {
            return true;
        }

        $grants = $this->uiAccess->notesAccessFor($user);
        $legacyKey = $this->uiAccess->noteEntityLegacyKey($entityKey);

        return (bool) ($grants[$legacyKey]['private_notes'] ?? false);
    }

    public function canCreateNote(User $user, string $entityKey, bool $isPrivate): bool
    {
        if (! $this->canViewNotes($user, $entityKey)) {
            return false;
        }

        if ($isPrivate && ! $this->canViewPrivateNotes($user, $entityKey)) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, EntityNote>  $notes
     * @return Collection<int, EntityNote>
     */
    public function filterVisible(Collection $notes, User $user, string $entityKey): Collection
    {
        if (! $this->canViewNotes($user, $entityKey)) {
            return collect();
        }

        $canViewPrivate = $this->canViewPrivateNotes($user, $entityKey);

        return $notes->filter(
            fn (EntityNote $note): bool => ! $note->is_private || $canViewPrivate,
        )->values();
    }
}
