<?php

use Database\Seeders\DatabaseSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(DatabaseSeeder::class);
});

test('legacy prep passes on demo seed without client dump', function () {
    $this->artisan('legacy:prep')
        ->assertSuccessful();
});

test('legacy prep strict fails when dump path is missing', function () {
    $this->artisan('legacy:prep', ['--strict' => true])
        ->assertFailed();
});
