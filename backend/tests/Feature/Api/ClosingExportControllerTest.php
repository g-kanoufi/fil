<?php

declare(strict_types=1);

use App\Models\Closing;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can export closings csv with fee lines', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    Closing::factory()->create([
        'title' => 'Acme Closing',
        'status' => 'pending',
        'extras' => [
            'fees' => [
                ['label' => 'Franchise fee', 'amount_cents' => 5000000],
                ['label' => 'Training fee', 'amount_cents' => 250000],
            ],
        ],
    ]);

    $response = $this->actingAs($user)
        ->get('/api/v1/closings/export');

    $response->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $body = $response->streamedContent();

    expect($body)->toContain('closing_id,title,status')
        ->and($body)->toContain('Acme Closing')
        ->and($body)->toContain('Franchise fee')
        ->and($body)->toContain('5000000');
});

test('closings export json format returns structured rows', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    Closing::factory()->create(['title' => 'JSON Closing']);

    $this->actingAs($user)
        ->getJson('/api/v1/closings/export?format=json')
        ->assertOk()
        ->assertJsonPath('data.0.title', 'JSON Closing');
});

test('user without leads.view cannot export closings', function () {
    $user = User::factory()->create();
    $user->givePermissionTo(['app.access']);

    $this->actingAs($user)
        ->getJson('/api/v1/closings/export')
        ->assertForbidden();
});
