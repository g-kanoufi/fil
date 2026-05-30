<?php

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can bulk send unit fdd', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $prospect = User::factory()->create(['email' => 'prospect@example.com']);
    $prospect->assignRole('prospect');

    $area = Area::factory()->create();
    $lead = Lead::factory()->create([
        'prospect_user_id' => $prospect->id,
        'area_id' => $area->id,
    ]);

    Fdd::query()->create([
        'type' => 'unit',
        'title' => 'Unit FDD',
        'area_id' => $area->id,
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/fdds/bulk-send', [
            'lead_ids' => [$lead->id],
            'type' => 'unit',
        ])
        ->assertCreated()
        ->assertJsonPath('data.sent.0.lead_id', $lead->id);

    $this->assertDatabaseHas('fdd_deliveries', [
        'lead_id' => $lead->id,
        'status' => 'sent',
    ]);

    $lead->refresh();
    expect($lead->lead_fdd_status)->toBe('disclosed');
});

test('summary endpoint returns counts', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    Fdd::query()->create(['type' => 'unit', 'title' => 'Unit', 'status' => 'active']);

    $this->actingAs($user)
        ->getJson('/api/v1/fdds/summary')
        ->assertOk()
        ->assertJsonPath('data.fdds.total', 1);
});
