<?php

use App\Models\Area;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can list leads', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');
    Lead::factory()->count(2)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/leads')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('franchisor without leads permission still has leads view', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/leads')
        ->assertOk();
});

test('prospect cannot list leads', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $this->actingAs($user)
        ->getJson('/api/v1/leads')
        ->assertForbidden();
});

test('franchisor can show lead with area and pipeline fields', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $area = Area::factory()->create(['name' => 'North Region']);
    $lead = Lead::factory()->create([
        'title' => 'Show Me Lead',
        'area_id' => $area->id,
        'lead_fdd_status' => 'active',
        'pipeline_phase' => 2,
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/leads/{$lead->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $lead->id)
        ->assertJsonPath('data.title', 'Show Me Lead')
        ->assertJsonPath('data.area_id', $area->id)
        ->assertJsonPath('data.pipeline_phase', 2)
        ->assertJsonStructure([
            'data' => [
                'id',
                'title',
                'pipeline_phase',
                'pipeline_phase_label',
                'application_status_label',
                'area_id',
                'phase_events',
            ],
        ]);
});

test('prospect cannot show lead', function () {
    $prospect = User::factory()->create();
    $prospect->assignRole('prospect');

    $lead = Lead::factory()->create();

    $this->actingAs($prospect)
        ->getJson("/api/v1/leads/{$lead->id}")
        ->assertForbidden();
});
