<?php

declare(strict_types=1);

use App\Jobs\Activity\RecordNavigationJob;
use App\Models\ActivityNavigation;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Services\Activity\ActivityRecorder;
use App\Services\Activity\NavigationActivityRecorder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('staff can ingest page views and upsert navigation rows', function () {
    $user = User::factory()->create(['name' => 'Nav User']);
    $user->assignRole('admin');

    $lead = Lead::factory()->create(['title' => 'Nav Lead']);

    $this->actingAs($user)
        ->postJson('/api/v1/activity/page-views', [
            'paths' => ["/reports/leads/{$lead->id}"],
        ])
        ->assertStatus(202)
        ->assertJsonPath('accepted', true);

    $this->assertDatabaseHas('activity_navigation', [
        'actor_user_id' => $user->id,
        'path_key' => "/reports/leads/{$lead->id}",
        'subject_type' => 'lead',
        'subject_id' => $lead->id,
        'view_count' => 1,
    ]);

    (new RecordNavigationJob($user->id, ["/reports/leads/{$lead->id}"]))->handle(app(NavigationActivityRecorder::class));

    expect(ActivityNavigation::query()->where('actor_user_id', $user->id)->count())->toBe(1);
    expect(ActivityNavigation::query()->first()?->view_count)->toBe(2);
});

test('sequential store detail visits create separate navigation rows', function () {
    $user = User::factory()->create(['name' => 'Store Nav']);
    $user->assignRole('admin');

    $storeA = Store::factory()->create(['name' => 'Unit Alpha']);
    $storeB = Store::factory()->create(['name' => 'Unit Beta']);

    $this->actingAs($user)
        ->postJson('/api/v1/activity/page-views', [
            'paths' => ["/reports/stores/{$storeA->id}"],
        ])
        ->assertStatus(202);

    $this->actingAs($user)
        ->postJson('/api/v1/activity/page-views', [
            'paths' => ["/reports/stores/{$storeB->id}"],
        ])
        ->assertStatus(202);

    expect(ActivityNavigation::query()->where('actor_user_id', $user->id)->count())->toBe(2);

    $this->actingAs($user)
        ->getJson('/api/v1/activity?category=navigation&days=30&limit=25')
        ->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonPath('meta.navigation_table_ready', true)
        ->assertJsonPath('data.0.subject.id', $storeB->id)
        ->assertJsonPath('data.1.subject.id', $storeA->id);
});

test('grid list paths are not recorded', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    (new RecordNavigationJob($user->id, ['/reports/leads']))->handle(app(NavigationActivityRecorder::class));

    expect(ActivityNavigation::query()->count())->toBe(0);
});

test('navigation feed uses activity_navigation table', function () {
    $user = User::factory()->create(['name' => 'Feed Nav']);
    $user->assignRole('admin');

    ActivityNavigation::query()->create([
        'actor_user_id' => $user->id,
        'actor_name' => $user->name,
        'path_key' => '/history',
        'period_bucket' => now()->toDateString(),
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'view_count' => 1,
        'source' => 'app',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/activity?category=navigation&days=30')
        ->assertOk()
        ->assertJsonPath('data.0.category', 'navigation')
        ->assertJsonPath('data.0.action', 'viewed')
        ->assertJsonPath('data.0.id', fn ($id) => str_starts_with((string) $id, 'nav_'));
});

test('default activity feed excludes navigation rows', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    ActivityNavigation::query()->create([
        'actor_user_id' => $user->id,
        'actor_name' => $user->name,
        'path_key' => '/history',
        'period_bucket' => now()->toDateString(),
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'view_count' => 1,
        'source' => 'app',
    ]);

    app(ActivityRecorder::class)->record(
        category: 'auth',
        action: 'login',
        summary: 'Signed in',
        actor: $user,
        subject: $user,
    );

    $this->actingAs($user)
        ->getJson('/api/v1/activity?days=30')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.category', 'auth');
});
