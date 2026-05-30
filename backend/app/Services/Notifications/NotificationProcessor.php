<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\Notifications\SendNotificationEmailJob;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationLog;
use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Support\Collection;

final class NotificationProcessor
{
    public function __construct(
        private readonly NotificationConditionalEvaluator $conditionals,
        private readonly NotificationRecipientResolver $recipients,
        private readonly NotificationMergeTagRenderer $mergeTags,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function process(
        string $triggerSlug,
        ?Lead $lead,
        array $context = [],
        ?User $actor = null,
        ?User $subjectUser = null,
    ): int {
        if ($lead === null && $subjectUser !== null && str_starts_with($triggerSlug, 'user.')) {
            return $this->processUserTrigger($triggerSlug, $subjectUser, $context, $actor);
        }

        if ($lead === null) {
            return 0;
        }

        /** @var Collection<int, NotificationRule> $rules */
        $rules = $this->matchingRules($triggerSlug);

        $changedFields = $context['changed_fields'] ?? [];
        $queued = 0;

        foreach ($rules as $rule) {
            if ($rule->shouldSkipDuplicateSend($lead)) {
                $this->logSkip($rule, $triggerSlug, 'Duplicate send suppressed', ['lead_id' => $lead->id]);

                continue;
            }

            if (! $this->conditionals->shouldSend($rule->conditionalRules(), $lead, $changedFields)) {
                $this->logSkip($rule, $triggerSlug, 'Conditional rules blocked send', ['lead_id' => $lead->id]);

                continue;
            }

            $recipientRows = $this->recipients->resolve($rule, $lead, $rule->recipientTokens());

            if ($recipientRows === []) {
                $this->logSkip($rule, $triggerSlug, 'No recipients resolved', ['lead_id' => $lead->id]);

                continue;
            }

            foreach ($recipientRows as $row) {
                $recipientUser = $row['user_id'] !== null
                    ? User::query()->find($row['user_id'])
                    : null;

                $subject = $this->mergeTags->render($rule->emailSubjectTemplate(), $lead, $recipientUser, $context);
                $body = $this->mergeTags->render($rule->emailBodyTemplate(), $lead, $recipientUser, $context);

                $this->queueEmail($rule, $triggerSlug, $lead->id, null, $row, $subject, $body, $actor);
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function processUserTrigger(
        string $triggerSlug,
        User $subjectUser,
        array $context,
        ?User $actor,
    ): int {
        /** @var Collection<int, NotificationRule> $rules */
        $rules = $this->matchingRules($triggerSlug);
        $queued = 0;

        foreach ($rules as $rule) {
            $recipientRows = $this->recipients->resolveForUser($rule, $subjectUser, $rule->recipientTokens());

            if ($recipientRows === []) {
                $this->logSkip($rule, $triggerSlug, 'No recipients resolved', ['user_id' => $subjectUser->id]);

                continue;
            }

            foreach ($recipientRows as $row) {
                $recipientUser = $row['user_id'] !== null
                    ? User::query()->find($row['user_id'])
                    : null;

                $subject = $this->mergeTags->renderForUser(
                    $rule->emailSubjectTemplate(),
                    $subjectUser,
                    $recipientUser,
                    $context,
                );
                $body = $this->mergeTags->renderForUser(
                    $rule->emailBodyTemplate(),
                    $subjectUser,
                    $recipientUser,
                    $context,
                );

                $this->queueEmail($rule, $triggerSlug, null, $subjectUser->id, $row, $subject, $body, $actor);
                $queued++;
            }
        }

        return $queued;
    }

    /**
     * @return Collection<int, NotificationRule>
     */
    private function matchingRules(string $triggerSlug): Collection
    {
        return NotificationRule::query()
            ->where('enabled', true)
            ->where(function ($query) use ($triggerSlug): void {
                $query->where('trigger_slug', $triggerSlug);

                /** @var array<string, string> $legacyMap */
                $legacyMap = config('fil-notifications.legacy_trigger_map', []);
                $legacySlugs = array_keys(array_filter($legacyMap, fn (string $mapped): bool => $mapped === $triggerSlug));

                if ($legacySlugs !== []) {
                    $query->orWhereIn('trigger_slug', $legacySlugs);
                }
            })
            ->get();
    }

    /**
     * @param  array{email: string, user_id: int|null, name: string|null}  $row
     */
    private function queueEmail(
        NotificationRule $rule,
        string $triggerSlug,
        ?int $leadId,
        ?int $subjectUserId,
        array $row,
        string $subject,
        string $body,
        ?User $actor,
    ): void {
        $delivery = NotificationDelivery::query()->create([
            'notification_rule_id' => $rule->id,
            'trigger_slug' => $triggerSlug,
            'channel' => 'email',
            'status' => 'queued',
            'recipient_email' => $row['email'],
            'recipient_user_id' => $row['user_id'],
            'lead_id' => $leadId,
            'subject' => $subject,
            'queued_at' => now(),
            'meta' => [
                'actor_user_id' => $actor?->id,
                'recipient_name' => $row['name'],
                'subject_user_id' => $subjectUserId,
            ],
        ]);

        SendNotificationEmailJob::dispatch($delivery->id, $body)
            ->onQueue(config('fil-notifications.queues.emails', 'emails'));
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function logSkip(NotificationRule $rule, string $triggerSlug, string $message, array $meta): void
    {
        NotificationLog::query()->create([
            'notification_rule_id' => $rule->id,
            'type' => 'skipped',
            'component' => $triggerSlug,
            'message' => $message,
            'logged_at' => now(),
            'meta' => $meta,
        ]);
    }
}
