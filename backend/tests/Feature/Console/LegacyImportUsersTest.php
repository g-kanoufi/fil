<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportUsersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_imports_staff_users_with_roles_by_default(): void
    {
        $fixture = base_path('tests/fixtures/legacy-users.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'users',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('users', [
            'legacy_user_id' => 502,
            'email' => 'admin@primeiv.test',
            'first_name' => 'Corp',
            'last_name' => 'Admin',
        ]);

        $admin = User::query()->where('legacy_user_id', 502)->first();
        $this->assertNotNull($admin);
        $this->assertTrue($admin->hasRole('franchisor'));

        $this->assertDatabaseMissing('users', ['legacy_user_id' => 501]);
    }

    public function test_all_users_flag_imports_prospects(): void
    {
        $fixture = base_path('tests/fixtures/legacy-users.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'users',
            '--all-users' => true,
            '--execute' => true,
        ])->assertSuccessful();

        $prospect = User::query()->where('legacy_user_id', 501)->first();
        $this->assertNotNull($prospect);
        $this->assertTrue($prospect->hasRole('prospect'));
    }
}
