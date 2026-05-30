<?php

declare(strict_types=1);
use App\Models\ClientSetting;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});
test('public branding returns client settings without auth', function () {
    ClientSetting::query()->updateOrCreate(
        ['key' => 'brandName'],
        ['value' => 'Acme Franchise'],
    );
    ClientSetting::query()->updateOrCreate(
        ['key' => 'highlightColor'],
        ['value' => '#0f766e'],
    );
    ClientSetting::query()->updateOrCreate(
        ['key' => 'logoUrl'],
        ['value' => 'https://cdn.example.com/logo.svg'],
    );

    $this->getJson('/api/public/v1/branding')
        ->assertOk()
        ->assertJsonPath('data.brandName', 'Acme Franchise')
        ->assertJsonPath('data.highlightColor', '#0f766e')
        ->assertJsonPath('data.logoUrl', 'https://cdn.example.com/logo.svg')
        ->assertJsonPath('data.clientBranding', true);
});
