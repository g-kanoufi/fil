<?php

declare(strict_types=1);
use App\Models\Lead;
use App\Services\Notifications\NotificationScheduleEvaluator;
use App\Services\Notifications\NotificationScheduleNormalizer;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->normalizer = app(NotificationScheduleNormalizer::class);
    $this->evaluator = app(NotificationScheduleEvaluator::class);
});
function scheduledLead(?string $waitingPeriodEndsAt): Lead
{
    return new Lead([
        'title' => 'Test',
        'status' => 'active',
        'waiting_period_ends_at' => $waitingPeriodEndsAt,
    ]);
}
test('legacy schedule normalizes waiting period field', function () {
    $schedule = $this->normalizer->normalize([
        'value' => '{postmeta/waiting_period_end_date}',
        'time_setting' => 'on',
        'time' => 0,
        'time_unit' => 'day',
        'lock' => 'forever',
    ]);

    expect($schedule['field'])->toBe('waiting_period_ends_at');
    expect($schedule['direction'])->toBe('on');
    expect($schedule['send_once'])->toBeTrue();
});
test('is due on same day', function () {
    Carbon::setTestNow('2026-05-28 10:00:00');

    $schedule = [
        'v' => 2,
        'field' => 'waiting_period_ends_at',
        'direction' => 'on',
        'offset_days' => 0,
        'offset_hours' => 0,
        'window_days' => 1,
        'send_once' => true,
    ];

    $lead = scheduledLead('2026-05-28 08:00:00');

    expect($this->evaluator->isDue($lead, $schedule))->toBeTrue();
    expect($this->evaluator->isDue(scheduledLead('2026-05-27 08:00:00'), $schedule))->toBeFalse();

    Carbon::setTestNow();
});
test('is due after offset', function () {
    Carbon::setTestNow('2026-05-30 10:00:00');

    $schedule = $this->normalizer->normalize([
        'value' => '{postmeta/waiting_period_end_date}',
        'time_setting' => 'after',
        'time' => 1,
        'time_unit' => 'day',
        'lock' => 'forever',
    ]);

    $lead = scheduledLead('2026-05-28 00:00:00');

    expect($this->evaluator->isDue($lead, $schedule))->toBeTrue();

    Carbon::setTestNow();
});
