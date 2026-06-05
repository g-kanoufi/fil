<?php

use App\Models\User;
use Database\Seeders\FieldSchemaSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});

test('lead owner has adminimize compatible restrictions', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    $response = $this->actingAs($user)->getJson('/api/v1/session');

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'ui_restrictions' => [
                    'tabs',
                    'leads' => ['menuItems', 'subMenuItems'],
                    'stores',
                    'nav_stores',
                    'features',
                ],
                'authorized_notes',
            ],
        ]);

    $tabs = $response->json('data.ui_restrictions.tabs');
    expect($tabs)->toContain('export_csv');

    $storeFilters = $response->json('data.ui_restrictions.stores.menuItems');
    expect($storeFilters)->toContain('store_area');
});

test('admin has no grid tab restrictions', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->getJson('/api/v1/session')
        ->assertOk()
        ->assertJsonPath('data.ui_restrictions.tabs', []);
});

test('app config returns options and menus', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $this->actingAs($user)
        ->getJson('/api/v1/app-config')
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'options' => ['brandName', 'topBarColor'],
                'menus' => ['top_menus', 'menus_with_columns'],
            ],
        ])
        ->assertJsonPath('data.menus.menus_with_columns.contacts.menuItems.contact_role.label', 'Contact roles')
        ->assertJsonStructure([
            'data' => [
                'menus' => [
                    'menus_with_columns' => [
                        'contacts' => [
                            'subMenuItems' => [
                                'contact_role',
                            ],
                        ],
                    ],
                ],
            ],
        ]);
});

test('app config exposes all unit statuses in store menus', function () {
    $this->seed(FieldSchemaSeeder::class);

    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $response = $this->actingAs($user)->getJson('/api/v1/app-config');

    $response->assertOk();

    $statuses = $response->json('data.menus.menus_with_columns.stores.subMenuItems.store_status');

    expect($statuses)->toHaveKeys(['pending', 'in_development', 'open', 'closed']);
});
