<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('staff can view own profile', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'phone' => '555-0100',
    ]);
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/profile')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.first_name', 'Jane')
        ->assertJsonPath('data.phone', '555-0100');
});
test('staff can update own profile', function () {
    $user = User::factory()->create([
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'password' => Hash::make('password'),
    ]);
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->patchJson('/api/v1/profile', [
            'first_name' => 'Janet',
            'last_name' => 'Smith',
            'email' => $user->email,
            'phone' => '555-0199',
        ])
        ->assertOk()
        ->assertJsonPath('data.first_name', 'Janet')
        ->assertJsonPath('data.phone', '555-0199')
        ->assertJsonPath('data.name', 'Janet Smith');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'first_name' => 'Janet',
        'phone' => '555-0199',
        'name' => 'Janet Smith',
    ]);
});
test('staff can change password with current password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->patchJson('/api/v1/profile', [
            'email' => $user->email,
            'current_password' => 'old-password',
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])
        ->assertOk();

    $user->refresh();
    expect(Hash::check('new-password-1', (string) $user->password))->toBeTrue();
});
test('profile update requires current password when changing password', function () {
    $user = User::factory()->create([
        'password' => Hash::make('old-password'),
    ]);
    $user->assignRole('lead_owner');

    $this->actingAs($user)
        ->patchJson('/api/v1/profile', [
            'email' => $user->email,
            'password' => 'new-password-1',
            'password_confirmation' => 'new-password-1',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['current_password']);
});
test('guest cannot access profile', function () {
    $this->getJson('/api/v1/profile')->assertUnauthorized();
});
