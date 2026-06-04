<?php

declare(strict_types=1);

use App\Models\ActivityEvent;
use App\Models\ActivityNavigation;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('store subject timeline returns activity and navigation', function () {
    $user = User::factory()->create(['name' => 'Store Viewer']);
    $user->assignRole('admin');

    $store = Store::factory()->create(['name' => 'Timeline Store']);

    ActivityEvent::query()->create([
        'occurred_at' => now(),
        'actor_user_id' => $user->id,
        'actor_name' => $user->name,
        'category' => 'store',
        'action' => 'updated',
        'summary' => 'Updated store',
        'subject_type' => 'store',
        'subject_id' => $store->id,
        'source' => 'app',
    ]);

    ActivityNavigation::query()->create([
        'actor_user_id' => $user->id,
        'actor_name' => $user->name,
        'path_key' => "/reports/stores/{$store->id}",
        'period_bucket' => now()->toDateString(),
        'subject_type' => 'store',
        'subject_id' => $store->id,
        'first_seen_at' => now(),
        'last_seen_at' => now(),
        'view_count' => 2,
        'source' => 'app',
    ]);

    $response = $this->actingAs($user)
        ->getJson("/api/v1/activity/subjects/store/{$store->id}?limit=15")
        ->assertOk();

    $categories = collect($response->json('data'))->pluck('category')->all();

    expect($categories)->toContain('navigation', 'store');
});

test('store subject timeline succeeds when activity_navigation table is missing', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $store = Store::factory()->create();

    ActivityEvent::query()->create([
        'occurred_at' => now(),
        'actor_user_id' => $user->id,
        'actor_name' => $user->name,
        'category' => 'store',
        'action' => 'updated',
        'summary' => 'Updated store',
        'subject_type' => 'store',
        'subject_id' => $store->id,
        'source' => 'app',
    ]);

    Schema::dropIfExists('activity_navigation');

    $this->actingAs($user)
        ->getJson("/api/v1/activity/subjects/store/{$store->id}?limit=15")
        ->assertOk()
        ->assertJsonPath('data.0.category', 'store');
});
