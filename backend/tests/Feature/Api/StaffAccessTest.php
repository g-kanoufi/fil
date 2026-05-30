<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class StaffAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
    }

    public function test_session_includes_roles_permissions_and_navigation(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@fil.test',
            'password' => Hash::make('secret'),
        ]);
        $user->assignRole('lead_owner');

        $response = $this->actingAs($user)->getJson('/api/v1/session');

        $response->assertOk()
            ->assertJsonPath('data.primary_role', 'lead_owner')
            ->assertJsonStructure([
                'data' => [
                    'permissions',
                    'navigation' => [
                        ['id', 'label', 'path'],
                    ],
                ],
            ]);

        $navigation = collect($response->json('data.navigation'))
            ->filter(fn (array $item): bool => isset($item['id']))
            ->pluck('id');

        $this->assertTrue($navigation->contains('leads'));
        $this->assertTrue($navigation->contains('settings'));
    }

    public function test_prospect_cannot_log_in_to_staff_app(): void
    {
        User::factory()->create([
            'email' => 'prospect@fil.test',
            'password' => Hash::make('secret'),
        ])->assignRole('prospect');

        $this->postJson('/api/v1/session', [
            'email' => 'prospect@fil.test',
            'password' => 'secret',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_unauthenticated_session_is_rejected(): void
    {
        $this->getJson('/api/v1/session')->assertUnauthorized();
    }

    public function test_staff_web_spa_requires_authentication(): void
    {
        $this->get('/app')->assertRedirect(route('login'));
    }

    public function test_authenticated_staff_can_load_spa_shell(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->get('/app')
            ->assertOk()
            ->assertSee('id="root"', false);
    }

    public function test_login_page_is_public(): void
    {
        $this->get('/app/login')->assertOk();
    }
}
