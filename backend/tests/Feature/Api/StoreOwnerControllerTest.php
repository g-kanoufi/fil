<?php

use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can sync store owners', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $owner = User::factory()->create();
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->putJson("/api/v1/stores/{$store->id}/owners", [
            'owners' => [
                ['user_id' => $owner->id, 'ownership_pct' => 50, 'role' => 'primary'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.0.user_id', $owner->id)
        ->assertJsonPath('data.0.ownership_pct', '50.00');

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/owners")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('franchisor can convert lead to store', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create(['title' => 'Converted Lead']);

    $this->actingAs($user)
        ->postJson("/api/v1/leads/{$lead->id}/convert")
        ->assertCreated()
        ->assertJsonPath('data.name', 'Converted Lead');

    $lead->refresh();
    expect($lead->status)->toBe('converted');
    expect(Store::query()->count())->toBe(1);
});
