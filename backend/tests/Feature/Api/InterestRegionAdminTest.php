<?php

declare(strict_types=1);

use App\Models\InterestRegion;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\InterestRegionSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(InterestRegionSeeder::class);
});

test('staff with leads view can list interest regions as a tree', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/interest-regions')
        ->assertOk()
        ->assertJsonPath('data.0.name', 'United States')
        ->assertJsonPath('data.0.children.0.code', 'AL');
});

test('staff with leads view can list flat subdivisions for selects', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $response = $this->actingAs($user)
        ->getJson('/api/v1/interest-regions?flat=1')
        ->assertOk();

    expect(collect($response->json('data'))->every(fn (array $row): bool => $row['parent_id'] !== null))->toBeTrue();
});

test('admin can create update and delete interest region subdivisions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $country = InterestRegion::query()->where('code', 'US')->whereNull('parent_id')->firstOrFail();

    $this->actingAs($admin)
        ->postJson('/api/v1/interest-regions', [
            'parent_id' => $country->id,
            'name' => 'Test Province',
            'code' => 'TP',
            'legacy_term_id' => 9001,
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'Test Province')
        ->assertJsonPath('data.legacy_term_id', 9001);

    $region = InterestRegion::query()->where('code', 'TP')->firstOrFail();

    $this->actingAs($admin)
        ->patchJson("/api/v1/interest-regions/{$region->id}", [
            'name' => 'Updated Province',
            'status' => 'inactive',
        ])
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Province')
        ->assertJsonPath('data.status', 'inactive');

    $this->actingAs($admin)
        ->deleteJson("/api/v1/interest-regions/{$region->id}")
        ->assertNoContent();

    $this->assertDatabaseMissing('interest_regions', ['id' => $region->id]);
});

test('admin cannot delete country with subdivisions', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $country = InterestRegion::query()->where('code', 'US')->whereNull('parent_id')->firstOrFail();

    $this->actingAs($admin)
        ->deleteJson("/api/v1/interest-regions/{$country->id}")
        ->assertStatus(422)
        ->assertJsonPath('data.code', 'interest_region_has_children');
});

test('admin cannot delete subdivision assigned to a lead', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $region = InterestRegion::query()
        ->where('code', 'TX')
        ->whereNotNull('parent_id')
        ->firstOrFail();

    Lead::factory()->create(['interest_region_id' => $region->id]);

    $this->actingAs($admin)
        ->deleteJson("/api/v1/interest-regions/{$region->id}")
        ->assertStatus(422)
        ->assertJsonPath('data.code', 'interest_region_in_use');
});

test('admin can set lead interest region', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $lead = Lead::factory()->create();
    $region = InterestRegion::query()
        ->where('code', 'CA')
        ->whereNotNull('parent_id')
        ->whereHas('parent', fn ($query) => $query->where('code', 'US'))
        ->firstOrFail();

    $this->actingAs($admin)
        ->patchJson("/api/v1/leads/{$lead->id}", [
            'interest_region_id' => $region->id,
        ])
        ->assertOk()
        ->assertJsonPath('data.interest_region_id', $region->id)
        ->assertJsonPath('data.interest_region.name', 'California');
});

test('employee cannot manage interest regions', function () {
    $user = User::factory()->create();
    $user->assignRole('employee');

    $this->actingAs($user)
        ->postJson('/api/v1/interest-regions', ['name' => 'Blocked'])
        ->assertForbidden();
});
