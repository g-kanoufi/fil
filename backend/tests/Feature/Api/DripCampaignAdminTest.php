<?php

declare(strict_types=1);
use App\Models\DripCampaign;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\DripCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DripCampaignSeeder::class);
});
test('admin can list drip campaigns with steps', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->getJson('/api/v1/drip-campaigns')
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'new-lead-welcome')
        ->assertJsonPath('data.0.steps.0.channel', 'email');
});
test('franchisor cannot manage drip campaigns', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/drip-campaigns')
        ->assertForbidden();
});
test('admin can create drip campaign with steps', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->postJson('/api/v1/drip-campaigns', [
            'name' => 'Follow-up sequence',
            'trigger_event' => 'lead_created',
            'status' => 'active',
            'steps' => [
                [
                    'sort_order' => 1,
                    'delay_days' => 0,
                    'channel' => 'email',
                    'subject' => 'Day 0',
                    'body_template' => 'Hello there',
                ],
                [
                    'sort_order' => 2,
                    'delay_days' => 3,
                    'channel' => 'email',
                    'subject' => 'Day 3',
                    'body_template' => 'Checking in',
                ],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Follow-up sequence')
        ->assertJsonPath('data.steps_count', 2);

    $this->assertDatabaseHas('drip_campaigns', [
        'slug' => 'follow-up-sequence',
        'status' => 'active',
    ]);
});
test('admin can update campaign and sync steps', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $campaign = DripCampaign::query()->where('slug', 'new-lead-welcome')->firstOrFail();
    $existingStep = DripStep::query()->where('drip_campaign_id', $campaign->id)->firstOrFail();

    $this->actingAs($admin)
        ->patchJson("/api/v1/drip-campaigns/{$campaign->id}", [
            'name' => 'Updated welcome',
            'steps' => [
                [
                    'id' => $existingStep->id,
                    'sort_order' => 1,
                    'delay_days' => 1,
                    'channel' => 'email',
                    'subject' => 'Updated subject',
                    'body_template' => 'Updated body',
                    'status' => 'active',
                ],
                [
                    'sort_order' => 2,
                    'delay_days' => 7,
                    'channel' => 'sms',
                    'subject' => null,
                    'body_template' => 'SMS follow-up',
                    'status' => 'active',
                ],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated welcome')
        ->assertJsonPath('data.steps_count', 2)
        ->assertJsonPath('data.steps.1.channel', 'sms');

    $this->assertDatabaseHas('drip_steps', [
        'id' => $existingStep->id,
        'subject' => 'Updated subject',
        'delay_days' => 1,
    ]);
});
test('destroy pauses campaign with enrollments', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $campaign = DripCampaign::query()->firstOrFail();
    DripEnrollment::query()->create([
        'drip_campaign_id' => $campaign->id,
        'lead_id' => Lead::factory()->create()->id,
        'status' => 'active',
    ]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/drip-campaigns/{$campaign->id}")
        ->assertNoContent();

    $this->assertDatabaseHas('drip_campaigns', [
        'id' => $campaign->id,
        'status' => 'paused',
    ]);
});
