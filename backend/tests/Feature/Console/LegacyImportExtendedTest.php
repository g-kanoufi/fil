<?php

use App\Models\Communication;
use App\Models\NotificationRule;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('import command imports staff users from fixture', function () {
    $this->seed(RolesAndPermissionsSeeder::class);

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
    expect(User::query()->count())->toBe(1);
});

test('import command imports communications from fixture', function () {
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

    expect(Communication::query()->count())->toBe(1);
});

test('import command imports notification rules with carriers and extras', function () {
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
    expect($rule)->not->toBeNull();
    expect($rule->body_html)->toBe('<p>Hello from legacy</p>');
    expect($rule->recipients)->toBe(['related:prospect']);
    expect($rule->conditionals['v'] ?? null)->toBe(2);
    expect($rule->conditionals['mode'] ?? null)->toBe('always');
});
