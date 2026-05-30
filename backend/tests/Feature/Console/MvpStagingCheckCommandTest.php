<?php

declare(strict_types=1);
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('staging check passes in local environment', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->artisan('mvp:staging-check')
        ->assertSuccessful();
});
test('staging check reports missing roles without crashing', function () {
    $this->artisan('mvp:staging-check')
        ->assertFailed()
        ->expectsOutputToContain('Roles & permissions');
});
test('staging check fails when demo users exist in production', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $this->app['env'] = 'production';
    config(['app.env' => 'production']);

    $admin = User::factory()->create(['email' => 'admin@fil.test']);
    $admin->assignRole('admin');

    $this->artisan('mvp:staging-check')
        ->assertFailed();
});
