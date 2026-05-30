<?php

use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\Signature;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can sign fdd delivery', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $lead = Lead::factory()->create();
    $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
    $delivery = FddDelivery::query()->create([
        'fdd_id' => $fdd->id,
        'lead_id' => $lead->id,
        'sent_at' => now(),
        'status' => 'sent',
        'delivery_method' => 'email',
        'meta' => [],
    ]);

    Signature::query()->create([
        'lead_id' => $lead->id,
        'fdd_delivery_id' => $delivery->id,
        'signer_user_id' => $user->id,
        'status' => 'pending',
    ]);

    $this->actingAs($user)
        ->postJson("/api/v1/fdd-deliveries/{$delivery->id}/sign", [
            'signed_name' => 'Jane Smith',
            'agree' => true,
        ])
        ->assertCreated()
        ->assertJsonPath('data.status', 'signed');

    $this->assertDatabaseHas('fdd_deliveries', ['id' => $delivery->id, 'status' => 'signed']);
    $lead->refresh();
    expect($lead->fdd_signed_at)->not->toBeNull();
    expect($lead->lead_fdd_status)->toBe('waiting_period');
    expect($lead->waiting_period_ends_at)->not->toBeNull();
});
