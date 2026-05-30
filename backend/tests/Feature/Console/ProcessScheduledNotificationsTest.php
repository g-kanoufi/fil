<?php

declare(strict_types=1);
use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Models\Lead;
use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

test('scheduled command dispatches only due leads', function () {
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
});
