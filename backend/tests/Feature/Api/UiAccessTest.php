<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
    }

    public function test_lead_owner_has_adminimize_compatible_restrictions(): void
    {
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
        $this->assertContains('export_csv', $tabs);

        $storeFilters = $response->json('data.ui_restrictions.stores.menuItems');
        $this->assertContains('store_area', $storeFilters);
    }

    public function test_admin_has_no_grid_tab_restrictions(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->getJson('/api/v1/session')
            ->assertOk()
            ->assertJsonPath('data.ui_restrictions.tabs', []);
    }

    public function test_app_config_returns_options_and_menus(): void
    {
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
    }
}
