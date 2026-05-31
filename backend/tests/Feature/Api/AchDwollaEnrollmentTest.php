<?php

declare(strict_types=1);
use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('franchisor can sandbox enroll store for ach', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [
            'sandbox' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.enrolled', true)
        ->assertJsonStructure(['data' => ['customer' => ['external_customer_id', 'status']]]);

    $this->assertDatabaseHas('ach_customers', [
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
    ]);
});
test('enroll requires external customer id in live mode', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [])
        ->assertStatus(422)
        ->assertJsonPath('data.message', 'external_customer_id is required.');
});
test('live enroll requires dwolla client token session', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [
            'external_customer_id' => 'cust-unverified',
        ])
        ->assertStatus(422)
        ->assertJsonPath('data.message', 'Request a Dwolla client token before enrolling.');
});
test('customer payload includes dwolla config', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}/ach/customer")
        ->assertOk()
        ->assertJsonPath('data.enrolled', false)
        ->assertJsonPath('data.dwolla_mode', 'sandbox')
        ->assertJsonPath('data.dwolla_environment', 'sandbox')
        ->assertJsonStructure(['data' => ['terms_url', 'privacy_url']]);
});
test('dwolla client token rejects when not configured', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/client-token", [
            'action' => 'customer.create',
        ])
        ->assertStatus(422)
        ->assertJsonPath('message', 'Dwolla is not configured.');
});
test('dwolla client token rejects actions outside the allowlist', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/client-token", [
            'action' => 'customer.delete',
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['action']);
});
test('certify ownership requires enrollment', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/certify-ownership")
        ->assertStatus(422)
        ->assertJsonPath('data.message', 'Store is not enrolled for ACH.');
});
test('certify ownership marks profile in sandbox', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');
    $store = Store::factory()->create();

    AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'sandbox-cust-test',
        'status' => 'verified',
        'profile' => ['sandbox' => true],
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/certify-ownership")
        ->assertOk()
        ->assertJsonPath('data.ownership_certified', true);

    $profile = AchCustomer::query()->first()?->profile;
    expect(is_array($profile))->toBeTrue();
    expect($profile['ownership_certified'] ?? false)->toBeTrue();
});
