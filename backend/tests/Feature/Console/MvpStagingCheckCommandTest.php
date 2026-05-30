<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MvpStagingCheckCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_staging_check_passes_in_local_environment(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $admin = \App\Models\User::factory()->create();
        $admin->assignRole('admin');

        $this->artisan('mvp:staging-check')
            ->assertSuccessful();
    }

    public function test_staging_check_reports_missing_roles_without_crashing(): void
    {
        $this->artisan('mvp:staging-check')
            ->assertFailed()
            ->expectsOutputToContain('Roles & permissions');
    }

    public function test_staging_check_fails_when_demo_users_exist_in_production(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        $admin = \App\Models\User::factory()->create(['email' => 'admin@fil.test']);
        $admin->assignRole('admin');

        $this->artisan('mvp:staging-check')
            ->assertFailed();
    }
}
