<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});

test('health endpoint returns ok', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonPath('service', 'fil-api');
});

test('user can login and fetch session', function () {
    $user = User::factory()->create([
        'email' => 'staff@fil.test',
        'password' => Hash::make('secret'),
        'first_name' => 'Staff',
        'last_name' => 'User',
    ]);
    $user->assignRole('lead_owner');

    $this->withSession([])
        ->postJson('/api/v1/session', [
            'email' => 'staff@fil.test',
            'password' => 'secret',
        ])->assertOk()
        ->assertJsonPath('data.email', 'staff@fil.test');

    $this->actingAs($user)
        ->getJson('/api/v1/session')
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Staff');
});

test('login fails with invalid credentials', function () {
    User::factory()->create([
        'email' => 'staff@fil.test',
        'password' => Hash::make('secret'),
    ])->assignRole('lead_owner');

    $this->postJson('/api/v1/session', [
        'email' => 'staff@fil.test',
        'password' => 'wrong',
    ])->assertUnprocessable();
});

test('admin login builds session without server error', function () {
    $admin = User::factory()->create([
        'email' => 'admin@fil.test',
        'password' => Hash::make('password'),
        'first_name' => 'FIL',
        'last_name' => 'Admin',
    ]);
    $admin->assignRole('admin');

    $this->postJson('/api/v1/session', [
        'email' => 'admin@fil.test',
        'password' => 'password',
    ])
        ->assertOk()
        ->assertJsonPath('data.name', 'FIL Admin')
        ->assertJsonPath('data.primary_role', 'admin');

    $this->actingAs($admin->fresh())
        ->getJson('/api/v1/session')
        ->assertOk()
        ->assertJsonPath('data.email', 'admin@fil.test');
});
