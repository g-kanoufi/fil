<?php

use App\Models\Closing;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can list closings', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Closing::factory()->count(2)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/closings')
        ->assertOk()
        ->assertJsonCount(2, 'data');
});

test('franchisor can view closing detail with workflow metadata', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $closing = Closing::factory()->create([
        'status' => 'pending',
        'extras' => [
            'fees' => [
                ['label' => 'Franchise fee', 'amount_cents' => 5000000],
            ],
        ],
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/closings/{$closing->id}")
        ->assertOk()
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.status_label', 'Pending')
        ->assertJsonPath('data.fee_total_cents', 5000000)
        ->assertJsonPath('data.allowed_status_transitions.0.key', 'scheduled');
});

test('franchisor can transition closing status', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $closing = Closing::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->patchJson("/api/v1/closings/{$closing->id}", [
            'status' => 'scheduled',
        ])
        ->assertOk()
        ->assertJsonPath('data.status', 'scheduled')
        ->assertJsonPath('data.status_label', 'Scheduled');

    $this->assertDatabaseHas('closings', [
        'id' => $closing->id,
        'status' => 'scheduled',
    ]);

    $this->assertDatabaseHas('activity_events', [
        'category' => 'closing',
        'action' => 'updated',
    ]);
});

test('invalid status transition is rejected', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $closing = Closing::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->patchJson("/api/v1/closings/{$closing->id}", [
            'status' => 'completed',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('franchisor can update fee lines', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $closing = Closing::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->patchJson("/api/v1/closings/{$closing->id}", [
            'fee_lines' => [
                ['label' => 'Initial franchise fee', 'amount_cents' => 4500000],
                ['label' => 'Training fee', 'amount_cents' => 250000],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('data.fee_total_cents', 4750000)
        ->assertJsonCount(2, 'data.fee_lines');

    $closing->refresh();

    expect($closing->extras['fees'] ?? null)->toBe([
        ['label' => 'Initial franchise fee', 'amount_cents' => 4500000],
        ['label' => 'Training fee', 'amount_cents' => 250000],
    ]);
});

test('view only user cannot update closing', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['app.access', 'leads.view']);

    $closing = Closing::factory()->create(['status' => 'pending']);

    $this->actingAs($user)
        ->patchJson("/api/v1/closings/{$closing->id}", [
            'status' => 'scheduled',
        ])
        ->assertForbidden();
});
