<?php

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can create and update store', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $create = $this->actingAs($user)
        ->postJson('/api/v1/stores', [
            'name' => 'PrimeIV Scottsdale',
            'store_status' => 'open',
        ])
        ->assertCreated()
        ->assertJsonPath('data.name', 'PrimeIV Scottsdale');

    $storeId = $create->json('data.id');

    $this->actingAs($user)
        ->patchJson("/api/v1/stores/{$storeId}", [
            'store_status' => 'pending',
        ])
        ->assertOk()
        ->assertJsonPath('data.store_status', 'pending');
});

test('lead owner cannot create store', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->postJson('/api/v1/stores', ['name' => 'Denied Store'])
        ->assertForbidden();
});

test('lead owner cannot list stores', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Store::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/stores')
        ->assertForbidden();
});
