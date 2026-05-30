<?php

declare(strict_types=1);
use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Jobs\Notifications\SendNotificationEmailJob;
use App\Mail\NotificationMail;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\User;
use App\Services\Mail\OutboundMailGuard;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('phase change queues notification jobs', function () {
    Bus::fake([ProcessNotificationTriggerJob::class, SendNotificationEmailJob::class]);

    NotificationRule::query()->create([
        'hash' => 'test-phase',
        'title' => 'Phase changed',
        'trigger_slug' => 'lead.phase_changed',
        'enabled' => true,
        'subject' => 'Phase update',
        'body_html' => '<p>Updated</p>',
        'recipients' => ['related:lead_owner'],
    ]);

    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $lead = Lead::factory()->create(['pipeline_phase' => 1, 'owner_user_id' => $user->id]);

    $this->actingAs($user)
        ->postJson("/api/v1/leads/{$lead->id}/transition-phase", ['to_phase' => 2])
        ->assertOk();

    Bus::assertDispatched(ProcessNotificationTriggerJob::class, function (ProcessNotificationTriggerJob $job): bool {
        return $job->triggerSlug === 'lead.phase_changed';
    });
});
test('notification profile preferences can be saved', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $rule = NotificationRule::query()->create([
        'hash' => 'profile-test',
        'title' => 'Marketing updates',
        'trigger_slug' => 'lead.created',
        'enabled' => true,
        'profile_roles' => ['lead_owner'],
        'recipients' => ['related:lead_owner'],
    ]);

    $this->actingAs($user)
        ->putJson('/api/v1/notifications/profile', [
            'preferences' => [(string) $rule->id => false],
        ])
        ->assertOk()
        ->assertJsonPath('data.saved', true);

    $this->assertDatabaseHas('user_notification_preferences', [
        'user_id' => $user->id,
        'notification_rule_id' => $rule->id,
        'opted_in' => false,
    ]);
});
test('send notification email job marks delivery sent', function () {
    Mail::fake();

    $lead = Lead::factory()->create();
    $rule = NotificationRule::query()->create([
        'hash' => 'send-test',
        'title' => 'Send test',
        'trigger_slug' => 'lead.created',
        'enabled' => true,
    ]);

    $delivery = NotificationDelivery::query()->create([
        'notification_rule_id' => $rule->id,
        'trigger_slug' => 'lead.created',
        'channel' => 'email',
        'status' => 'queued',
        'recipient_email' => 'prospect@example.com',
        'lead_id' => $lead->id,
        'subject' => 'Hello',
        'queued_at' => now(),
    ]);

    (new SendNotificationEmailJob($delivery->id, '<p>Hi</p>'))->handle(app(OutboundMailGuard::class));

    $this->assertDatabaseHas('notification_deliveries', [
        'id' => $delivery->id,
        'status' => 'sent',
    ]);

    Mail::assertSent(NotificationMail::class);
});
