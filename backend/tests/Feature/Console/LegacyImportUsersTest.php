<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('imports staff users with roles by default', function () {
    $fixture = base_path('tests/fixtures/legacy-users.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'users',
        '--execute' => true,
    ])->assertSuccessful();

    $this->assertDatabaseHas('users', [
        'legacy_user_id' => 502,
        'email' => 'admin@primeiv.test',
        'first_name' => 'Corp',
        'last_name' => 'Admin',
    ]);

    $admin = User::query()->where('legacy_user_id', 502)->first();
    expect($admin)->not->toBeNull();
    expect($admin->hasRole('franchisor'))->toBeTrue();

    $this->assertDatabaseMissing('users', ['legacy_user_id' => 501]);
});

test('all users flag imports prospects', function () {
    $fixture = base_path('tests/fixtures/legacy-users.sql');

    $this->artisan('legacy:import', [
        'dump' => $fixture,
        '--prefix' => 'wp_9_',
        '--only' => 'users',
        '--all-users' => true,
        '--execute' => true,
    ])->assertSuccessful();

    $prospect = User::query()->where('legacy_user_id', 501)->first();
    expect($prospect)->not->toBeNull();
    expect($prospect->hasRole('prospect'))->toBeTrue();
});
