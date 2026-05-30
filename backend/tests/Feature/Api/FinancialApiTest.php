<?php

use App\Models\AchTransfer;
use App\Models\PosConnection;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can calculate and list royalty periods', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create(['royalty_config' => ['default_rate' => 0.06]]);

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/royalty-periods/calculate", [
            'gross_revenue' => 10000,
            'order_count' => 120,
        ])
        ->assertCreated()
        ->assertJsonPath('data.gross_revenue', '10000.00')
        ->assertJsonPath('data.total_royalties', '600.00')
        ->assertJsonCount(1, 'data.line_items');

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/royalty-periods")
        ->assertOk()
        ->assertJsonCount(1, 'data');
});

test('franchisor can view royalty period detail', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();
    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'recorded_at' => now(),
        'gross_revenue' => 5000,
        'total_royalties' => 300,
        'status' => 'calculated',
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/royalty-periods/{$period->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $period->id);
});

test('lead owner cannot access royalties', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/royalty-periods")
        ->assertForbidden();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/royalty-periods/calculate", [
            'gross_revenue' => 100,
        ])
        ->assertForbidden();
});

test('franchisor can list ach transfers', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $transfer = AchTransfer::query()->create([
        'store_id' => $store->id,
        'transferred_at' => now(),
        'provider' => 'dwolla',
        'status' => 1,
        'amount' => 600,
        'royalty_name' => 'Unit royalty',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/ach-transfers')
        ->assertOk()
        ->assertJsonPath('data.0.id', $transfer->id);

    $this->actingAs($user)
        ->getJson("/api/v1/ach-transfers/{$transfer->id}")
        ->assertOk()
        ->assertJsonPath('data.amount', '600.00');
});

test('franchisor can list and sync pos connections', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $connection = PosConnection::query()->create([
        'store_id' => $store->id,
        'provider' => 'square',
        'external_location_id' => 'LOC123',
        'status' => 'active',
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/pos-connections")
        ->assertOk()
        ->assertJsonPath('data.0.provider', 'square');

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/pos-connections/{$connection->id}/sync")
        ->assertStatus(202)
        ->assertJsonPath('data.status', 'synced');
});

test('franchisor can trigger ach from royalty period', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'weekly',
        'period_start' => now()->startOfWeek(),
        'period_end' => now()->endOfWeek(),
        'recorded_at' => now(),
        'gross_revenue' => 5000,
        'total_royalties' => 300,
        'status' => 'calculated',
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/royalty-periods/{$period->id}/trigger-ach")
        ->assertCreated()
        ->assertJsonPath('data.amount', '300.00')
        ->assertJsonPath('data.store_id', $store->id);
});

test('lead owner cannot access ach or pos', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->getJson('/api/v1/ach-transfers')
        ->assertForbidden();

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/pos-connections")
        ->assertForbidden();
});
