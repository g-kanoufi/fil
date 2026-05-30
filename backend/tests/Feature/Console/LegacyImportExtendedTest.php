<?php

namespace Tests\Feature\Console;

use App\Models\Communication;
use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LegacyImportExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_command_imports_staff_users_from_fixture(): void
    {
        $this->seed(\Database\Seeders\RolesAndPermissionsSeeder::class);

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
        ]);

        $this->assertDatabaseMissing('users', ['legacy_user_id' => 501]);
        $this->assertSame(1, User::query()->count());
    }

    public function test_import_command_imports_communications_from_fixture(): void
    {
        $fixture = base_path('tests/fixtures/legacy-communications.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'communications',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('communications', [
            'external_message_id' => 'fixture-msg-1',
            'message' => 'Hello from legacy import test',
        ]);

        $this->assertSame(1, Communication::query()->count());
    }

    public function test_import_command_imports_notification_rules_with_carriers_and_extras(): void
    {
        $fixture = base_path('tests/fixtures/legacy-notifications.sql');

        $this->artisan('legacy:import', [
            'dump' => $fixture,
            '--prefix' => 'wp_9_',
            '--only' => 'notifications',
            '--execute' => true,
        ])->assertSuccessful();

        $this->assertDatabaseHas('notification_rules', [
            'hash' => 'fixture-notify-hash',
            'title' => 'Fixture welcome email',
            'trigger_slug' => 'zrz_user_registered',
            'subject' => 'Welcome {{user_name}}',
        ]);

        $rule = NotificationRule::query()->where('hash', 'fixture-notify-hash')->first();
        $this->assertNotNull($rule);
        $this->assertSame('<p>Hello from legacy</p>', $rule->body_html);
        $this->assertSame(['related:prospect'], $rule->recipients);
        $this->assertSame(2, $rule->conditionals['v'] ?? null);
        $this->assertSame('always', $rule->conditionals['mode'] ?? null);
    }
}
