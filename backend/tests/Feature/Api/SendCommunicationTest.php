<?php

declare(strict_types=1);
use App\Mail\DripStepMail;
use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\CommunicationSuppressionService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('staff can send email to lead prospect', function () {
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

    Mail::assertSent(DripStepMail::class);
});
test('staff can send sms to lead prospect', function () {
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
});
test('view only staff cannot send communications', function () {
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
});
test('staff cannot send to suppressed email', function () {
    Mail::fake();

    app(CommunicationSuppressionService::class)
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
});
