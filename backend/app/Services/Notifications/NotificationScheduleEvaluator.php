<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Lead;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

final class NotificationScheduleEvaluator
{
    /**
     * @param  array<string, mixed>  $schedule
     */
    public function isDue(Lead $lead, array $schedule, ?CarbonInterface $now = null): bool
    {
        $field = (string) ($schedule['field'] ?? '');

        if ($field === '') {
            return false;
        }

        $value = $lead->getAttribute($field);

        if ($value === null || $value === '') {
            return false;
        }

        $now = $now ?? now();
        $anchor = Carbon::parse($value);
        $offsetDays = (int) ($schedule['offset_days'] ?? 0);
        $offsetHours = (int) ($schedule['offset_hours'] ?? 0);
        $windowDays = max(1, (int) ($schedule['window_days'] ?? 1));
        $direction = (string) ($schedule['direction'] ?? 'on');

        $target = match ($direction) {
            'before' => $anchor->copy()->startOfDay()->subDays($offsetDays)->subHours($offsetHours),
            'after' => $anchor->copy()->startOfDay()->addDays($offsetDays)->addHours($offsetHours),
            default => $anchor->copy()->startOfDay()->addDays($offsetDays)->addHours($offsetHours),
        };

        return match ($direction) {
            'before' => $now->betweenIncluded(
                $target->copy()->subDays($windowDays - 1)->startOfDay(),
                $target->copy()->endOfDay(),
            ),
            'after' => ($schedule['send_once'] ?? true)
                ? $now->gte($target)
                : $now->gte($target) && $now->lte($target->copy()->addDays($windowDays - 1)->endOfDay()),
            default => $target->isSameDay($now),
        };
    }
}
