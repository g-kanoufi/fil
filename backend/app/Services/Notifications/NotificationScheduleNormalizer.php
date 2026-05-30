<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Converts legacy imported schedule extras into FIL v2 schedule schema.
 *
 * Canonical:
 * {
 *   "v": 2,
 *   "field": "waiting_period_ends_at",
 *   "direction": "on"|"after"|"before",
 *   "offset_days": 0,
 *   "offset_hours": 0,
 *   "window_days": 1,
 *   "send_once": true
 * }
 */
final class NotificationScheduleNormalizer
{
    /**
     * @param  array<string, mixed>|null  $schedule
     * @return array<string, mixed>|null
     */
    public function normalize(?array $schedule): ?array
    {
        if ($schedule === null || $schedule === []) {
            return null;
        }

        if (($schedule['v'] ?? null) === 2 && filled($schedule['field'] ?? null)) {
            return $this->sanitizeV2($schedule);
        }

        return $this->fromLegacy($schedule);
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @return array<string, mixed>|null
     */
    private function fromLegacy(array $schedule): ?array
    {
        $mergeTag = (string) ($schedule['value'] ?? $schedule['merge_tag'] ?? '');
        $field = $this->fieldFromMergeTag($mergeTag);

        if ($field === null) {
            return null;
        }

        $timeSetting = strtolower((string) ($schedule['time_setting'] ?? 'on'));
        $direction = match ($timeSetting) {
            'before' => 'before',
            'after' => 'after',
            default => 'on',
        };

        $time = max(0, (int) ($schedule['time'] ?? 0));
        $unit = strtolower((string) ($schedule['time_unit'] ?? 'day'));

        [$offsetDays, $offsetHours] = $this->toOffset($time, $unit);

        $windowTime = max(0, (int) ($schedule['window_time'] ?? 0));
        $windowUnit = strtolower((string) ($schedule['window_time_unit'] ?? 'day'));
        [$windowDays] = $this->toOffset($windowTime > 0 ? $windowTime : 1, $windowUnit);

        $lock = strtolower((string) ($schedule['lock'] ?? 'forever'));

        return [
            'v' => 2,
            'field' => $field,
            'direction' => $direction,
            'offset_days' => $offsetDays,
            'offset_hours' => $offsetHours,
            'window_days' => max(1, $windowDays),
            'send_once' => $lock !== 'never',
        ];
    }

    /**
     * @param  array<string, mixed>  $schedule
     * @return array<string, mixed>
     */
    private function sanitizeV2(array $schedule): array
    {
        $direction = in_array($schedule['direction'] ?? '', ['on', 'after', 'before'], true)
            ? $schedule['direction']
            : 'on';

        return [
            'v' => 2,
            'field' => (string) $schedule['field'],
            'direction' => $direction,
            'offset_days' => max(0, (int) ($schedule['offset_days'] ?? 0)),
            'offset_hours' => max(0, (int) ($schedule['offset_hours'] ?? 0)),
            'window_days' => max(1, (int) ($schedule['window_days'] ?? 1)),
            'send_once' => (bool) ($schedule['send_once'] ?? true),
        ];
    }

    private function fieldFromMergeTag(string $mergeTag): ?string
    {
        if (preg_match('/\{postmeta\/([a-z0-9_\-]+)\}/i', $mergeTag, $matches)) {
            return $this->mapFieldName($matches[1]);
        }

        if (preg_match('/postmeta\/([a-z0-9_\-]+)/i', $mergeTag, $matches)) {
            return $this->mapFieldName($matches[1]);
        }

        if ($mergeTag !== '' && ! str_contains($mergeTag, '{')) {
            return $this->mapFieldName($mergeTag);
        }

        return null;
    }

    private function mapFieldName(string $field): string
    {
        /** @var array<string, string> $map */
        $map = config('fil-notifications.schedule_field_map', []);

        return $map[$field] ?? $field;
    }

    /**
     * @return array{0: int, 1: int}
     */
    private function toOffset(int $time, string $unit): array
    {
        return match ($unit) {
            'hour', 'hours' => [0, $time],
            'week', 'weeks' => [$time * 7, 0],
            default => [$time, 0],
        };
    }
}
