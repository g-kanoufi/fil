<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\User;
use App\Models\UserNotificationPreference;
use Illuminate\Support\Collection;

final class NotificationPreferenceService
{
    /**
     * @return array{notifications: list<array<string, mixed>>, disabled_notification_ids: list<int>}
     */
    public function profileListFor(User $user, string $list = 'platform'): array
    {
        $user->loadMissing('roles');

        /** @var Collection<int, NotificationRule> $rules */
        $rules = NotificationRule::query()
            ->where('enabled', true)
            ->whereNotNull('profile_roles')
            ->orderBy('title')
            ->get()
            ->filter(fn (NotificationRule $rule): bool => $this->ruleVisibleToUser($rule, $user, $list));

        $disabled = UserNotificationPreference::query()
            ->where('user_id', $user->id)
            ->where('opted_in', false)
            ->pluck('notification_rule_id')
            ->all();

        return [
            'notifications' => $rules->map(fn (NotificationRule $rule): array => [
                'id' => $rule->id,
                'title' => $rule->title,
                'trigger_slug' => $rule->trigger_slug,
            ])->values()->all(),
            'disabled_notification_ids' => $disabled,
        ];
    }

    /**
     * @param  array<int, bool>  $preferences  notification_rule_id => opted_in
     */
    public function syncPreferences(User $user, array $preferences): void
    {
        foreach ($preferences as $ruleId => $optedIn) {
            UserNotificationPreference::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'notification_rule_id' => (int) $ruleId,
                ],
                ['opted_in' => (bool) $optedIn],
            );
        }
    }

    private function ruleVisibleToUser(NotificationRule $rule, User $user, string $list): bool
    {
        $roles = $rule->profile_roles ?? [];

        if ($roles === []) {
            return $list === 'platform';
        }

        $userRoles = $user->roles->pluck('name')->all();

        foreach ($roles as $role) {
            if (in_array($role, $userRoles, true)) {
                return true;
            }
        }

        return false;
    }
}
