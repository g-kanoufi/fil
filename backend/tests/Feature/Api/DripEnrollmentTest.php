<?php

namespace Tests\Feature\Api;

use App\Jobs\Drips\SendDripStepJob;
use App\Models\DripCampaign;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DripCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DripEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(DripCampaignSeeder::class);
    }

    public function test_public_lead_intake_enrolls_in_drip_campaign(): void
    {
        Queue::fake();

        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_dev',
            'email' => 'drip@example.com',
            'first_name' => 'Drip',
            'last_name' => 'Test',
        ])->assertCreated();

        $lead = Lead::query()->whereHas('prospect', fn ($q) => $q->where('email', 'drip@example.com'))->first();
        $this->assertNotNull($lead);

        $this->assertDatabaseHas('drip_enrollments', [
            'lead_id' => $lead->id,
            'status' => 'active',
        ]);

        Queue::assertPushed(SendDripStepJob::class);
    }

    public function test_phase_transition_to_one_enrolls_existing_lead(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = Lead::factory()->create(['pipeline_phase' => 2]);

        $this->actingAs($user)
            ->postJson("/api/v1/leads/{$lead->id}/transition-phase", ['to_phase' => 1])
            ->assertOk();

        $this->assertSame(1, DripEnrollment::query()->where('lead_id', $lead->id)->count());
        Queue::assertPushed(SendDripStepJob::class);
    }

    public function test_send_drip_step_job_creates_communication(): void
    {
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

        \Illuminate\Support\Facades\Mail::fake();

        (new SendDripStepJob($run->id))->handle(
            new \App\Services\Sms\TwilioSmsService(),
            app(\App\Services\Drips\DripEnrollmentService::class),
        );

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\DripStepMail::class, function ($mail) use ($prospect): bool {
            return $mail->hasTo($prospect->email);
        });

        $this->assertDatabaseHas('communications', [
            'lead_id' => $lead->id,
            'type' => 'email',
            'status' => 'sent',
        ]);

        $run->refresh();
        $this->assertSame('sent', $run->status);
    }

    public function test_send_drip_step_job_schedules_next_step(): void
    {
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
            new \App\Services\Sms\TwilioSmsService(),
            app(\App\Services\Drips\DripEnrollmentService::class),
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

        $this->assertSame('active', $enrollment->fresh()?->status);
        $this->assertSame($nextStep->id, $enrollment->fresh()?->current_step_id);

        Queue::assertPushed(SendDripStepJob::class, 1);
    }

    public function test_send_drip_step_job_completes_enrollment_after_last_step(): void
    {
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
            new \App\Services\Sms\TwilioSmsService(),
            app(\App\Services\Drips\DripEnrollmentService::class),
        );

        $enrollment->refresh();

        $this->assertSame('completed', $enrollment->status);
        $this->assertNotNull($enrollment->completed_at);
        $this->assertNull($enrollment->current_step_id);
        $this->assertSame(1, $enrollment->stepRuns()->count());
    }

    public function test_send_drip_step_job_fails_without_recipient_contact(): void
    {
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
            new \App\Services\Sms\TwilioSmsService(),
            app(\App\Services\Drips\DripEnrollmentService::class),
        );

        $this->assertSame('failed', $run->fresh()?->status);
        $this->assertSame('active', $enrollment->fresh()?->status);
        $this->assertSame(1, $enrollment->fresh()?->stepRuns()->count());
    }
}
