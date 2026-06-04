<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\EntityNote;
use App\Models\User;
use App\Policies\Concerns\ChecksFilPermissions;
use App\Services\Operations\EntityNoteAccessService;
use App\Services\Operations\EntityNoteSubjectResolver;

final class EntityNotePolicy
{
    use ChecksFilPermissions;

    public function __construct(
        private readonly EntityNoteAccessService $access,
        private readonly EntityNoteSubjectResolver $subjects,
    ) {}

    public function view(User $user, EntityNote $note): bool
    {
        $subject = $this->subjects->resolve($note->subject_type, (int) $note->subject_id);
        $this->subjects->authorizeView($user, $subject);

        $entityKey = $this->subjects->entityKey($subject);

        if ($note->is_private && ! $this->access->canViewPrivateNotes($user, $entityKey)) {
            return false;
        }

        return $this->access->canViewNotes($user, $entityKey);
    }

    public function update(User $user, EntityNote $note): bool
    {
        if ((int) $note->author_user_id === (int) $user->id) {
            return $this->view($user, $note);
        }

        return $this->allows($user, 'notes.manage') && $this->view($user, $note);
    }

    public function delete(User $user, EntityNote $note): bool
    {
        return $this->update($user, $note);
    }
}
