<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Lead;
use App\Models\NotificationRule;
use Illuminate\Support\Collection;

final class ScheduledNotificationProcessor
{
    public function __construct(
        private readonly NotificationScheduleEvaluator $scheduleEvaluator,
        private readonly NotificationScheduleNormalizer $scheduleNormalizer,
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function process(int $limit = 200): int
    {
        /** @var Collection<int, NotificationRule> $rules */
        $rules = NotificationRule::query()
            ->where('enabled', true)
            ->where(function ($query): void {
                $query->whereNotNull('schedule')
                    ->orWhere('trigger_slug', 'scheduled.leads')
                    ->orWhere('trigger_slug', 'like', 'scheduled/%')
                    ->orWhere('trigger_slug', 'post/application/zrz-send-one-campaign');
            })
            ->get();

        $dispatched = 0;

        foreach ($rules as $rule) {
            $schedule = $rule->effectiveSchedule();

            if ($schedule === null) {
                continue;
            }

            $field = (string) $schedule['field'];
            $lookbackDays = (int) config('fil-notifications.schedule_lookback_days', 90);
            $lookaheadDays = (int) config('fil-notifications.schedule_lookahead_days', 7);

            Lead::query()
                ->where('status', 'active')
                ->whereNotNull($field)
                ->where($field, '>=', now()->subDays($lookbackDays))
                ->where($field, '<=', now()->addDays($lookaheadDays))
                ->orderBy('id')
                ->limit($limit)
                ->get()
                ->each(function (Lead $lead) use ($rule, $schedule, &$dispatched): void {
                    if (! $this->scheduleEvaluator->isDue($lead, $schedule)) {
                        return;
                    }

                    if ($rule->shouldSkipDuplicateSend($lead)) {
                        return;
                    }

                    $this->dispatcher->dispatch($rule->normalizedTriggerSlug(), $lead, [
                        'scheduled' => true,
                        'schedule' => $schedule,
                        'changed_fields' => [],
                    ]);

                    $dispatched++;
                });
        }

        return $dispatched;
    }

    /**
     * Normalize schedule JSON on an existing rule (import / maintenance).
     */
    public function normalizeRuleSchedule(NotificationRule $rule): ?array
    {
        $raw = $rule->schedule
            ?? $rule->extras['schedule']
            ?? $rule->config['extras']['schedule']
            ?? $rule->config['schedule']
            ?? null;

        if (! is_array($raw)) {
            return null;
        }

        return $this->scheduleNormalizer->normalize($raw);
    }
}
