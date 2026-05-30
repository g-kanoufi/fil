<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Models\Lead;
use App\Models\NotificationRule;
use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class ProcessScheduledNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_scheduled_command_dispatches_only_due_leads(): void
    {
        Queue::fake();

        NotificationRule::query()->create([
            'hash' => 'sched-wp-end',
            'title' => 'Waiting period reminder',
            'trigger_slug' => 'scheduled.leads',
            'enabled' => true,
            'channel' => 'email',
            'subject' => 'Reminder',
            'body_html' => '<p>Hi</p>',
            'recipients' => ['related:prospect'],
            'schedule' => [
                'v' => 2,
                'field' => 'waiting_period_ends_at',
                'direction' => 'on',
                'offset_days' => 0,
                'offset_hours' => 0,
                'window_days' => 1,
                'send_once' => true,
            ],
        ]);

        $dueLead = Lead::factory()->create([
            'status' => 'active',
            'waiting_period_ends_at' => now()->startOfDay(),
        ]);

        Lead::factory()->create([
            'status' => 'active',
            'waiting_period_ends_at' => now()->addDays(10),
        ]);

        $this->artisan('notifications:process-scheduled')
            ->assertSuccessful();

        Queue::assertPushed(ProcessNotificationTriggerJob::class, 1);
    }
}
