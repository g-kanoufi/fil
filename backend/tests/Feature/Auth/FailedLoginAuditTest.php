<?php

declare(strict_types=1);

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('failed staff login is recorded in the audit log', function () {
    User::factory()->create(['email' => 'staff@fil.test']);

    $this->postJson('/api/v1/session', [
        'email' => 'staff@fil.test',
        'password' => 'definitely-the-wrong-password',
    ])->assertStatus(422);

    $this->assertDatabaseHas('activity_events', [
        'category' => 'auth',
        'action' => 'login_failed',
    ]);
});

test('login attempt for an unknown email is recorded without an actor', function () {
    $this->postJson('/api/v1/session', [
        'email' => 'ghost@fil.test',
        'password' => 'whatever',
    ])->assertStatus(422);

    $this->assertDatabaseHas('activity_events', [
        'category' => 'auth',
        'action' => 'login_failed',
        'actor_user_id' => null,
    ]);
});
