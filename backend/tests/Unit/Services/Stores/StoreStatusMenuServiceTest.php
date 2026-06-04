<?php

use App\Services\Stores\StoreStatusMenuService;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(FieldSchemaSeeder::class);
});

test('enrichStoreMenus includes all configured unit statuses even when empty in database', function () {
    $service = app(StoreStatusMenuService::class);

    $menu = $service->enrichStoreMenus([
        'menuItems' => [
            'store_status' => ['label' => 'Unit statuses', 'slug' => 'store_status'],
        ],
        'subMenuItems' => [],
    ]);

    $slugs = array_keys($menu['subMenuItems']['store_status'] ?? []);

    expect($slugs)->toContain('pending', 'in_development', 'open', 'closed');
});
