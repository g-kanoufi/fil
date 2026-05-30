<?php

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can list communications for lead', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create();

    Communication::query()->create([
        'lead_id' => $lead->id,
        'type' => 'email',
        'direction' => 'outbound',
        'message' => 'Hello prospect',
        'status' => 'sent',
        'sent_at' => now(),
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/communications?lead_id={$lead->id}")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.message', 'Hello prospect');
});

test('lead owner can list communications', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->getJson('/api/v1/communications')
        ->assertOk();
});
