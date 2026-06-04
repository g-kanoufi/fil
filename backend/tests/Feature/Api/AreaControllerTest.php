<?php

use App\Models\Area;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can list and manage areas', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/areas')
        ->assertOk();

    $response = $this->actingAs($user)
        ->postJson('/api/v1/areas', [
            'name' => 'Southwest Territory',
            'territory' => [
                'country' => 'US',
                'subdivisions' => ['AZ', 'NM'],
            ],
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Southwest Territory')
        ->assertJsonPath('data.territory.country', 'US');

    $areaId = (int) $response->json('data.id');

    $this->actingAs($user)
        ->patchJson("/api/v1/areas/{$areaId}", ['name' => 'Southwest'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Southwest');

    $this->actingAs($user)
        ->deleteJson("/api/v1/areas/{$areaId}")
        ->assertNoContent();
});

test('cannot delete area assigned to a store', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $area = Area::factory()->create();
    Store::factory()->create(['area_id' => $area->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/areas/{$area->id}")
        ->assertStatus(422)
        ->assertJsonPath('data.code', 'area_in_use');
});

test('geography endpoint returns north america catalog', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/geography/north-america')
        ->assertOk()
        ->assertJsonPath('data.countries.0.code', 'US')
        ->assertJsonPath('data.countries.1.code', 'CA');
});

test('interest region defaults can be synced', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->postJson('/api/v1/interest-regions/sync-defaults')
        ->assertOk()
        ->assertJsonPath('data.countries', 2)
        ->assertJsonPath('data.subdivisions', 64);
});
