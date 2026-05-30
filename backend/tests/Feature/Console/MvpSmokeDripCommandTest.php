<?php

declare(strict_types=1);
use Database\Seeders\DripCampaignSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DripCampaignSeeder::class);
});
test('smoke drip command sends through mailhog sink', function () {
    Http::fake(function () {
        static $calls = 0;
        $calls++;

        return Http::response(['total' => $calls >= 3 ? 1 : 0], 200);
    });

    $this->artisan('mvp:smoke-drip')
        ->assertSuccessful()
        ->expectsOutputToContain('Drip step sent');

    $this->assertDatabaseHas('communications', [
        'type' => 'email',
        'status' => 'sent',
    ]);
});
