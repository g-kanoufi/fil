<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

final class SendCommunicationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_send_email_to_lead_prospect(): void
    {
        Mail::fake();

        $staff = User::factory()->create(['name' => 'Staff Sender']);
        $staff->assignRole('admin');

        $prospect = User::factory()->create([
            'email' => 'prospect@example.com',
            'name' => 'Prospect User',
        ]);

        $lead = Lead::factory()->create([
            'title' => 'Email Lead',
            'prospect_user_id' => $prospect->id,
        ]);

        $this->actingAs($staff)
            ->postJson('/api/v1/communications', [
                'lead_id' => $lead->id,
                'channel' => 'email',
                'subject' => 'Hello there',
                'message' => 'Thanks for your interest.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'email')
            ->assertJsonPath('data.direction', 'outbound')
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('communications', [
            'lead_id' => $lead->id,
            'type' => 'email',
            'message' => 'Thanks for your interest.',
        ]);

        Mail::assertSent(\App\Mail\DripStepMail::class);
    }

    public function test_staff_can_send_sms_to_lead_prospect(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('franchisor');

        $prospect = User::factory()->create([
            'phone' => '+15551234567',
            'name' => 'SMS Prospect',
        ]);

        $lead = Lead::factory()->create([
            'prospect_user_id' => $prospect->id,
        ]);

        $this->actingAs($staff)
            ->postJson('/api/v1/communications', [
                'lead_id' => $lead->id,
                'channel' => 'sms',
                'message' => 'Quick follow-up text.',
            ])
            ->assertCreated()
            ->assertJsonPath('data.type', 'sms')
            ->assertJsonPath('data.status', 'sent');

        $this->assertDatabaseHas('communications', [
            'lead_id' => $lead->id,
            'type' => 'sms',
            'message' => 'Quick follow-up text.',
        ]);
    }

    public function test_view_only_staff_cannot_send_communications(): void
    {
        $staff = User::factory()->create();
        $staff->assignRole('lead_owner');

        $lead = Lead::factory()->create();

        $this->actingAs($staff)
            ->postJson('/api/v1/communications', [
                'lead_id' => $lead->id,
                'channel' => 'email',
                'message' => 'Should fail',
            ])
            ->assertForbidden();
    }

    public function test_staff_cannot_send_to_suppressed_email(): void
    {
        Mail::fake();

        app(\App\Services\Communications\CommunicationSuppressionService::class)
            ->suppress('email', 'blocked@example.com', 'bounce', 'test');

        $staff = User::factory()->create();
        $staff->assignRole('admin');

        $prospect = User::factory()->create(['email' => 'blocked@example.com']);
        $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

        $this->actingAs($staff)
            ->postJson('/api/v1/communications', [
                'lead_id' => $lead->id,
                'channel' => 'email',
                'message' => 'Should not send',
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'failed');

        Mail::assertNothingSent();
    }
}
