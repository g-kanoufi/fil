<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Models\Lead;
use App\Services\Notifications\NotificationScheduleEvaluator;
use App\Services\Notifications\NotificationScheduleNormalizer;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class NotificationScheduleEvaluatorTest extends TestCase
{
    private NotificationScheduleNormalizer $normalizer;

    private NotificationScheduleEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = app(NotificationScheduleNormalizer::class);
        $this->evaluator = app(NotificationScheduleEvaluator::class);
    }

    private function lead(?string $waitingPeriodEndsAt): Lead
    {
        return new Lead([
            'title' => 'Test',
            'status' => 'active',
            'waiting_period_ends_at' => $waitingPeriodEndsAt,
        ]);
    }

    public function test_legacy_schedule_normalizes_waiting_period_field(): void
    {
        $schedule = $this->normalizer->normalize([
            'value' => '{postmeta/waiting_period_end_date}',
            'time_setting' => 'on',
            'time' => 0,
            'time_unit' => 'day',
            'lock' => 'forever',
        ]);

        $this->assertSame('waiting_period_ends_at', $schedule['field']);
        $this->assertSame('on', $schedule['direction']);
        $this->assertTrue($schedule['send_once']);
    }

    public function test_is_due_on_same_day(): void
    {
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

        $lead = $this->lead('2026-05-28 08:00:00');

        $this->assertTrue($this->evaluator->isDue($lead, $schedule));
        $this->assertFalse($this->evaluator->isDue($this->lead('2026-05-27 08:00:00'), $schedule));

        Carbon::setTestNow();
    }

    public function test_is_due_after_offset(): void
    {
        Carbon::setTestNow('2026-05-30 10:00:00');

        $schedule = $this->normalizer->normalize([
            'value' => '{postmeta/waiting_period_end_date}',
            'time_setting' => 'after',
            'time' => 1,
            'time_unit' => 'day',
            'lock' => 'forever',
        ]);

        $lead = $this->lead('2026-05-28 00:00:00');

        $this->assertTrue($this->evaluator->isDue($lead, $schedule));

        Carbon::setTestNow();
    }
}
