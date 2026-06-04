<?php

declare(strict_types=1);

use App\Jobs\Notifications\ProcessNotificationTriggerJob;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('store inspection due processor queues notifications for due stores', function () {
    Queue::fake();

    $owner = User::factory()->create(['email' => 'owner@fil.test']);
    $owner->assignRole('franchisee');

    $store = Store::factory()->create([
        'status' => 'active',
        'next_inspection_at' => now()->addDays(3)->toDateString(),
    ]);

    StoreOwner::query()->create([
        'store_id' => $store->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    NotificationRule::query()->create([
        'hash' => 'store-inspection-due-test',
        'title' => 'Inspection due',
        'trigger_slug' => 'store.inspection_due',
        'enabled' => true,
        'subject' => 'Inspection due for {fil/store_name}',
        'body_html' => '<p>Next inspection on {fil/next_inspection_at}</p>',
        'recipients' => ['related:store_owner'],
    ]);

    $this->artisan('notifications:process-scheduled')
        ->assertSuccessful();

    Queue::assertPushed(ProcessNotificationTriggerJob::class, function (ProcessNotificationTriggerJob $job) use ($store): bool {
        return $job->storeId === $store->id && $job->triggerSlug === 'store.inspection_due';
    });
});

test('store inspection due processor skips duplicate sends', function () {
    Queue::fake();

    $owner = User::factory()->create(['email' => 'owner@fil.test']);

    $store = Store::factory()->create([
        'status' => 'active',
        'next_inspection_at' => now()->addDay()->toDateString(),
    ]);

    StoreOwner::query()->create([
        'store_id' => $store->id,
        'user_id' => $owner->id,
        'role' => 'owner',
    ]);

    $rule = NotificationRule::query()->create([
        'hash' => 'store-inspection-due-dedupe',
        'title' => 'Inspection due',
        'trigger_slug' => 'store.inspection_due',
        'enabled' => true,
        'subject' => 'Inspection due',
        'body_html' => '<p>Due soon</p>',
        'recipients' => ['owner@fil.test'],
    ]);

    NotificationDelivery::query()->create([
        'notification_rule_id' => $rule->id,
        'trigger_slug' => 'store.inspection_due',
        'channel' => 'email',
        'status' => 'sent',
        'recipient_email' => 'owner@fil.test',
        'subject' => 'Inspection due',
        'queued_at' => now(),
        'meta' => ['store_id' => $store->id],
    ]);

    $this->artisan('notifications:process-scheduled')
        ->assertSuccessful();

    Queue::assertNotPushed(ProcessNotificationTriggerJob::class);
});

test('franchisee dashboard includes scoped store ops summary', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    $visible = Store::factory()->create([
        'name' => 'Scottsdale',
        'status' => 'active',
        'next_inspection_at' => now()->addDays(5)->toDateString(),
        'opened_at' => now()->subMonths(6)->toDateString(),
    ]);
    Store::factory()->create(['name' => 'Hidden store', 'status' => 'active']);

    StoreOwner::query()->create([
        'store_id' => $visible->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.store_ops.inspection_due_count', 1)
        ->assertJsonPath('data.store_ops.stores.0.name', 'Scottsdale')
        ->assertJsonPath('data.store_ops.stores.0.next_inspection_at', $visible->next_inspection_at->toDateString());
});

test('store resource exposes next inspection date', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $store = Store::factory()->create([
        'next_inspection_at' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->getJson("/api/v1/stores/{$store->id}")
        ->assertOk()
        ->assertJsonPath('data.next_inspection_at', '2026-07-01');
});
