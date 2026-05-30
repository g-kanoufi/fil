<?php

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class SessionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(UiAccessSeeder::class);
    }

    public function test_health_endpoint_returns_ok(): void
    {
        $this->getJson('/api/health')
            ->assertOk()
            ->assertJsonPath('status', 'ok')
            ->assertJsonPath('service', 'fil-api');
    }

    public function test_user_can_login_and_fetch_session(): void
    {
        $user = User::factory()->create([
            'email' => 'staff@fil.test',
            'password' => Hash::make('secret'),
            'first_name' => 'Staff',
            'last_name' => 'User',
        ]);
        $user->assignRole('lead_owner');

        $this->withSession([])
            ->postJson('/api/v1/session', [
                'email' => 'staff@fil.test',
                'password' => 'secret',
            ])->assertOk()
            ->assertJsonPath('data.email', 'staff@fil.test');

        $this->actingAs($user)
            ->getJson('/api/v1/session')
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Staff');
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'staff@fil.test',
            'password' => Hash::make('secret'),
        ])->assignRole('lead_owner');

        $this->postJson('/api/v1/session', [
            'email' => 'staff@fil.test',
            'password' => 'wrong',
        ])->assertUnprocessable();
    }

    public function test_admin_login_builds_session_without_server_error(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@fil.test',
            'password' => Hash::make('password'),
            'first_name' => 'FIL',
            'last_name' => 'Admin',
        ]);
        $admin->assignRole('admin');

        $this->postJson('/api/v1/session', [
            'email' => 'admin@fil.test',
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('data.name', 'FIL Admin')
            ->assertJsonPath('data.primary_role', 'admin');

        $this->actingAs($admin->fresh())
            ->getJson('/api/v1/session')
            ->assertOk()
            ->assertJsonPath('data.email', 'admin@fil.test');
    }
}
