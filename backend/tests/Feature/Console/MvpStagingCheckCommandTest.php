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
test('staging check warns when csp is report-only in staging', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->app['env'] = 'staging';
    config([
        'app.env' => 'staging',
        'session.secure' => true,
        'session.same_site' => 'lax',
        'fil-security.csp.enabled' => true,
        'fil-security.csp.report_only' => true,
        'fil.embed.site_keys' => ['pk_live_stagingtest1234567890'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'services.dwolla.webhook_secret' => 'test-secret',
    ]);

    $this->artisan('mvp:staging-check')
        ->assertSuccessful()
        ->expectsOutputToContain('FIL_CSP_REPORT_ONLY=true');
});
test('staging check fails when csp is disabled in staging', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->app['env'] = 'staging';
    config([
        'app.env' => 'staging',
        'session.secure' => true,
        'session.same_site' => 'lax',
        'fil-security.csp.enabled' => false,
        'fil.embed.site_keys' => ['pk_live_stagingtest1234567890'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'services.dwolla.webhook_secret' => 'test-secret',
    ]);

    $this->artisan('mvp:staging-check')
        ->assertFailed()
        ->expectsOutputToContain('FIL_CSP_ENABLED=true');
});
