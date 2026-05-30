<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Area;
use App\Models\Lead;
use App\Models\NotificationRule;
use App\Models\User;
use App\Models\UserNotificationPreference;

final class NotificationRecipientResolver
{
    /**
     * @param  list<string>  $tokens
     * @return list<array{email: string, user_id: int|null, name: string|null}>
     */
    public function resolve(NotificationRule $rule, Lead $lead, array $tokens): array
    {
        $resolved = [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            if (filter_var($token, FILTER_VALIDATE_EMAIL)) {
                $resolved[] = ['email' => $token, 'user_id' => null, 'name' => null];

                continue;
            }

            if (str_starts_with($token, 'related:')) {
                $role = substr($token, strlen('related:'));
                $users = $this->usersForRelatedRole($lead, $role);

                foreach ($users as $user) {
                    if (filled($user->email)) {
                        $resolved[] = [
                            'email' => $user->email,
                            'user_id' => $user->id,
                            'name' => $user->name,
                        ];
                    }
                }

                continue;
            }

            if (str_contains($token, 'lead_owner') || str_contains($token, 'Lead Owner')) {
                $owner = $lead->owner;

                if ($owner !== null && filled($owner->email)) {
                    $resolved[] = ['email' => $owner->email, 'user_id' => $owner->id, 'name' => $owner->name];
                }

                continue;
            }

            if (str_contains($token, 'prospect') || str_contains($token, '{user/user_email}')) {
                $prospect = $lead->prospect;

                if ($prospect !== null && filled($prospect->email)) {
                    $resolved[] = [
                        'email' => $prospect->email,
                        'user_id' => $prospect->id,
                        'name' => $prospect->name,
                    ];
                }

                continue;
            }

            if (preg_match('/\{email\s+(.+?)\}/', $token, $matches)) {
                $email = trim($matches[1]);

                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $resolved[] = ['email' => $email, 'user_id' => null, 'name' => null];
                }
            }
        }

        return $this->dedupeRecipients($resolved, $rule);
    }

    /**
     * @param  list<string>  $tokens
     * @return list<array{email: string, user_id: int|null, name: string|null}>
     */
    public function resolveForUser(NotificationRule $rule, User $subjectUser, array $tokens): array
    {
        $resolved = [];

        foreach ($tokens as $token) {
            $token = trim($token);

            if ($token === '') {
                continue;
            }

            if (filter_var($token, FILTER_VALIDATE_EMAIL)) {
                $resolved[] = ['email' => $token, 'user_id' => null, 'name' => null];

                continue;
            }

            if ($token === 'related:user' || str_contains($token, 'user_email')) {
                if (filled($subjectUser->email)) {
                    $resolved[] = [
                        'email' => $subjectUser->email,
                        'user_id' => $subjectUser->id,
                        'name' => $subjectUser->name,
                    ];
                }

                continue;
            }

            if (str_starts_with($token, 'related:')) {
                continue;
            }
        }

        if ($resolved === [] && filled($subjectUser->email)) {
            $resolved[] = [
                'email' => $subjectUser->email,
                'user_id' => $subjectUser->id,
                'name' => $subjectUser->name,
            ];
        }

        return $this->dedupeRecipients($resolved, $rule);
    }

    /**
     * @param  list<array{email: string, user_id: int|null, name: string|null}>  $resolved
     * @return list<array{email: string, user_id: int|null, name: string|null}>
     */
    private function dedupeRecipients(array $resolved, NotificationRule $rule): array
    {
        $deduped = [];
        $seen = [];

        foreach ($resolved as $row) {
            $key = strtolower($row['email']);

            if (isset($seen[$key])) {
                continue;
            }

            if ($row['user_id'] !== null && ! $this->userOptedIn($row['user_id'], $rule)) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $row;
        }

        $max = (int) config('fil-notifications.max_recipients_per_send', 50);

        return array_slice($deduped, 0, $max);
    }

    /**
     * @return list<User>
     */
    private function usersForRelatedRole(Lead $lead, string $role): array
    {
        return match ($role) {
            'lead_owner', 'owner' => $lead->owner ? [$lead->owner] : [],
            'prospect' => $lead->prospect ? [$lead->prospect] : [],
            'area_rep' => $this->areaRepsForLead($lead),
            default => [],
        };
    }

    /**
     * @return list<User>
     */
    private function areaRepsForLead(Lead $lead): array
    {
        if ($lead->area_id === null) {
            return [];
        }

        $area = Area::query()->find($lead->area_id);

        if ($area === null) {
            return [];
        }

        $extras = is_array($area->extras ?? null) ? $area->extras : [];
        $repUserId = $extras['rep_user_id'] ?? $extras['area_rep_user_id'] ?? null;

        if ($repUserId === null) {
            return [];
        }

        $rep = User::query()->find((int) $repUserId);

        return $rep !== null && filled($rep->email) ? [$rep] : [];
    }

    private function userOptedIn(int $userId, NotificationRule $rule): bool
    {
        $preference = UserNotificationPreference::query()
            ->where('user_id', $userId)
            ->where('notification_rule_id', $rule->id)
            ->value('opted_in');

        return $preference === null ? true : (bool) $preference;
    }
}
