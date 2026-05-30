<?php

declare(strict_types=1);

namespace App\Services\Notifications;

final class NotificationRulePayloadNormalizer
{
    public function __construct(
        private readonly NotificationConditionNormalizer $conditionNormalizer,
        private readonly NotificationScheduleNormalizer $scheduleNormalizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $conditionals
     * @return array<string, mixed>|null
     */
    public function normalizeConditionals(?array $conditionals): ?array
    {
        if ($conditionals === null) {
            return null;
        }

        $normalized = $this->conditionNormalizer->normalize($conditionals);

        if ($normalized === null) {
            return null;
        }

        if (($normalized['mode'] ?? 'always') === 'always') {
            return ['v' => 2, 'mode' => 'always', 'groups' => []];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>|null  $schedule
     * @return array<string, mixed>|null
     */
    public function normalizeSchedule(?array $schedule): ?array
    {
        if ($schedule === null || $schedule === []) {
            return null;
        }

        return $this->scheduleNormalizer->normalize($schedule);
    }
}
