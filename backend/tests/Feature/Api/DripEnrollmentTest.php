<?php

use App\Jobs\Drips\SendDripStepJob;
use App\Mail\DripStepMail;
use App\Models\DripCampaign;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Models\Lead;
use App\Models\User;
use App\Services\Drips\DripEnrollmentService;
use App\Services\Sms\TwilioSmsService;
use Database\Seeders\DripCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DripCampaignSeeder::class);
});

test('public lead intake enrolls in drip campaign', function () {
    Queue::fake();

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev',
        'email' => 'drip@example.com',
        'first_name' => 'Drip',
        'last_name' => 'Test',
    ])->assertCreated();

    $lead = Lead::query()->whereHas('prospect', fn ($q) => $q->where('email', 'drip@example.com'))->first();
    expect($lead)->not->toBeNull();

    $this->assertDatabaseHas('drip_enrollments', [
        'lead_id' => $lead->id,
        'status' => 'active',
    ]);

    Queue::assertPushed(SendDripStepJob::class);
});

test('phase transition to one enrolls existing lead', function () {
    Queue::fake();

    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create(['pipeline_phase' => 2]);

    $this->actingAs($user)
        ->postJson("/api/v1/leads/{$lead->id}/transition-phase", ['to_phase' => 1])
        ->assertOk();

    expect(DripEnrollment::query()->where('lead_id', $lead->id)->count())->toBe(1);
    Queue::assertPushed(SendDripStepJob::class);
});

test('send drip step job creates communication', function () {
    $campaign = DripCampaign::query()->first();
    $step = DripStep::query()->where('drip_campaign_id', $campaign->id)->first();
    $prospect = User::factory()->create(['email' => 'prospect@example.com']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

    $enrollment = DripEnrollment::query()->create([
        'drip_campaign_id' => $campaign->id,
        'lead_id' => $lead->id,
        'status' => 'active',
    ]);

    $run = $enrollment->stepRuns()->create([
        'drip_step_id' => $step->id,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    Mail::fake();

    (new SendDripStepJob($run->id))->handle(
        new TwilioSmsService,
        app(DripEnrollmentService::class),
    );

    Mail::assertSent(DripStepMail::class, function ($mail) use ($prospect): bool {
        return $mail->hasTo($prospect->email);
    });

    $this->assertDatabaseHas('communications', [
        'lead_id' => $lead->id,
        'type' => 'email',
        'status' => 'sent',
    ]);

    $run->refresh();
    expect($run->status)->toBe('sent');
});

test('send drip step job schedules next step', function () {
    Queue::fake();

    $campaign = DripCampaign::query()->firstOrFail();
    DripStep::query()->create([
        'drip_campaign_id' => $campaign->id,
        'sort_order' => 2,
        'delay_days' => 2,
        'delay_hours' => 0,
        'channel' => 'email',
        'subject' => 'Follow-up',
        'body_template' => 'Checking in again.',
        'status' => 'active',
    ]);

    $firstStep = DripStep::query()
        ->where('drip_campaign_id', $campaign->id)
        ->orderBy('sort_order')
        ->firstOrFail();

    $prospect = User::factory()->create(['email' => 'prospect@example.com']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id, 'eligible_for_drip' => true]);

    $enrollment = DripEnrollment::query()->create([
        'drip_campaign_id' => $campaign->id,
        'lead_id' => $lead->id,
        'status' => 'active',
    ]);

    $run = $enrollment->stepRuns()->create([
        'drip_step_id' => $firstStep->id,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    Mail::fake();

    (new SendDripStepJob($run->id))->handle(
        new TwilioSmsService,
        app(DripEnrollmentService::class),
    );

    $this->assertDatabaseHas('drip_step_runs', [
        'drip_enrollment_id' => $enrollment->id,
        'drip_step_id' => $firstStep->id,
        'status' => 'sent',
    ]);

    $nextStep = DripStep::query()
        ->where('drip_campaign_id', $campaign->id)
        ->where('sort_order', 2)
        ->firstOrFail();

    $this->assertDatabaseHas('drip_step_runs', [
        'drip_enrollment_id' => $enrollment->id,
        'drip_step_id' => $nextStep->id,
        'status' => 'pending',
    ]);

    expect($enrollment->fresh()?->status)->toBe('active');
    expect($enrollment->fresh()?->current_step_id)->toBe($nextStep->id);

    Queue::assertPushed(SendDripStepJob::class, 1);
});

test('send drip step job completes enrollment after last step', function () {
    Queue::fake();

    $campaign = DripCampaign::query()->firstOrFail();
    $onlyStep = DripStep::query()
        ->where('drip_campaign_id', $campaign->id)
        ->orderBy('sort_order')
        ->firstOrFail();

    $prospect = User::factory()->create(['email' => 'prospect@example.com']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id, 'eligible_for_drip' => true]);

    $enrollment = DripEnrollment::query()->create([
        'drip_campaign_id' => $campaign->id,
        'lead_id' => $lead->id,
        'status' => 'active',
    ]);

    $run = $enrollment->stepRuns()->create([
        'drip_step_id' => $onlyStep->id,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    Mail::fake();

    (new SendDripStepJob($run->id))->handle(
        new TwilioSmsService,
        app(DripEnrollmentService::class),
    );

    $enrollment->refresh();

    expect($enrollment->status)->toBe('completed');
    expect($enrollment->completed_at)->not->toBeNull();
    expect($enrollment->current_step_id)->toBeNull();
    expect($enrollment->stepRuns()->count())->toBe(1);
});

test('send drip step job fails without recipient contact', function () {
    $campaign = DripCampaign::query()->firstOrFail();
    $step = DripStep::query()->where('drip_campaign_id', $campaign->id)->firstOrFail();
    $lead = Lead::factory()->create(['prospect_user_id' => null, 'eligible_for_drip' => true]);

    $enrollment = DripEnrollment::query()->create([
        'drip_campaign_id' => $campaign->id,
        'lead_id' => $lead->id,
        'status' => 'active',
    ]);

    $run = $enrollment->stepRuns()->create([
        'drip_step_id' => $step->id,
        'scheduled_at' => now(),
        'status' => 'pending',
    ]);

    (new SendDripStepJob($run->id))->handle(
        new TwilioSmsService,
        app(DripEnrollmentService::class),
    );

    expect($run->fresh()?->status)->toBe('failed');
    expect($enrollment->fresh()?->status)->toBe('active');
    expect($enrollment->fresh()?->stepRuns()->count())->toBe(1);
});
