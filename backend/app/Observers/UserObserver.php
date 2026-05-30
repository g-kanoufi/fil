<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;

final class UserObserver
{
    private const PROFILE_FIELDS = ['name', 'first_name', 'last_name', 'email', 'phone'];

    public function __construct(
        private readonly NotificationDispatcher $notifications,
    ) {}

    public function created(User $user): void
    {
        if (! config('fil-notifications.emit_user_registered', true)) {
            return;
        }

        $this->notifications->dispatch('user.registered', null, [], null, $user);
    }

    public function updated(User $user): void
    {
        if (! $user->wasChanged()) {
            return;
        }

        /** @var list<string> $changed */
        $changed = array_values(array_diff(array_keys($user->getChanges()), ['updated_at', 'password', 'remember_token']));

        $profileFields = array_values(array_intersect($changed, self::PROFILE_FIELDS));

        if ($profileFields === []) {
            return;
        }

        $this->notifications->dispatch('user.profile_updated', null, [
            'changed_fields' => $profileFields,
        ], null, $user->fresh());
    }
}
