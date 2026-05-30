<?php

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('public lead intake validates payload', function () {
    $this->postJson('/api/public/v1/leads', ['site_key' => 'pk_dev'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'first_name', 'last_name']);
});

test('public lead intake rejects invalid site key', function () {
    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'invalid',
        'email' => 'prospect@example.com',
        'first_name' => 'Pat',
        'last_name' => 'Lee',
    ])->assertForbidden();
});

test('public lead intake creates prospect and lead', function () {
    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_dev',
        'email' => 'prospect@example.com',
        'first_name' => 'Pat',
        'last_name' => 'Lee',
        'phone' => '+15551234567',
    ])->assertCreated()
        ->assertJsonPath('data.title', 'Pat Lee Application');

    $this->assertDatabaseHas('users', ['email' => 'prospect@example.com']);
    expect(Lead::query()->count())->toBe(1);

    $prospect = User::query()->where('email', 'prospect@example.com')->first();
    expect($prospect->hasRole('prospect'))->toBeTrue();
});

test('public lead intake accepts site key from header', function () {
    $this->postJson('/api/public/v1/leads', [
        'email' => 'header-prospect@example.com',
        'first_name' => 'Header',
        'last_name' => 'Prospect',
    ], ['X-FIL-Site-Key' => 'pk_dev'])
        ->assertCreated()
        ->assertJsonPath('data.title', 'Header Prospect Application');
});

test('public form config requires site key', function () {
    $this->getJson('/api/public/v1/form-config')
        ->assertUnauthorized();
});

test('public form config returns schema with site key', function () {
    $this->getJson('/api/public/v1/form-config?site_key=pk_dev')
        ->assertOk()
        ->assertJsonPath('data.form_key', 'lead_short');
});
