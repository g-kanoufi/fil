<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

final class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_view_own_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '555-0100',
        ]);
        $user->assignRole('franchisor');

        $this->actingAs($user)
            ->getJson('/api/v1/profile')
            ->assertOk()
            ->assertJsonPath('data.email', $user->email)
            ->assertJsonPath('data.first_name', 'Jane')
            ->assertJsonPath('data.phone', '555-0100');
    }

    public function test_staff_can_update_own_profile(): void
    {
        $user = User::factory()->create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'password' => Hash::make('password'),
        ]);
        $user->assignRole('franchisor');

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', [
                'first_name' => 'Janet',
                'last_name' => 'Smith',
                'email' => $user->email,
                'phone' => '555-0199',
            ])
            ->assertOk()
            ->assertJsonPath('data.first_name', 'Janet')
            ->assertJsonPath('data.phone', '555-0199')
            ->assertJsonPath('data.name', 'Janet Smith');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'first_name' => 'Janet',
            'phone' => '555-0199',
            'name' => 'Janet Smith',
        ]);
    }

    public function test_staff_can_change_password_with_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', [
                'email' => $user->email,
                'current_password' => 'old-password',
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertOk();

        $user->refresh();
        $this->assertTrue(Hash::check('new-password-1', (string) $user->password));
    }

    public function test_profile_update_requires_current_password_when_changing_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('old-password'),
        ]);
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->patchJson('/api/v1/profile', [
                'email' => $user->email,
                'password' => 'new-password-1',
                'password_confirmation' => 'new-password-1',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['current_password']);
    }

    public function test_guest_cannot_access_profile(): void
    {
        $this->getJson('/api/v1/profile')->assertUnauthorized();
    }
}
