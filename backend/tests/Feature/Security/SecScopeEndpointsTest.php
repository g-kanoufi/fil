<?php

declare(strict_types=1);
use App\Models\AchTransfer;
use App\Models\Area;
use App\Models\Lead;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('area rep leads index is scoped', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visible = Lead::factory()->create(['area_id' => $area->id, 'title' => 'Visible lead']);
    Lead::factory()->create(['area_id' => null, 'title' => 'Hidden lead']);

    $response = $this->actingAs($rep)->getJson('/api/v1/leads')->assertOk();

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain('Visible lead');
    expect($titles)->not->toContain('Hidden lead');
});
test('franchisee store index is scoped', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    $visible = Store::factory()->create(['name' => 'My store']);
    Store::factory()->create(['name' => 'Other store']);

    StoreOwner::query()->create([
        'store_id' => $visible->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)->getJson('/api/v1/stores')->assertOk();

    $names = collect($response->json('data'))->pluck('name')->all();
    expect($names)->toContain('My store');
    expect($names)->not->toContain('Other store');
});
test('activity subject timeline requires lead access', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visibleLead = Lead::factory()->create(['area_id' => $area->id]);
    $hiddenLead = Lead::factory()->create(['area_id' => null]);

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/lead/{$visibleLead->id}")
        ->assertOk();

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/lead/{$hiddenLead->id}")
        ->assertForbidden();
});
test('activity subject timeline requires store access', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visibleStore = Store::factory()->create(['area_id' => $area->id]);
    $hiddenStore = Store::factory()->create(['area_id' => null]);

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/store/{$visibleStore->id}")
        ->assertOk();

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/store/{$hiddenStore->id}")
        ->assertForbidden();
});
test('activity subject timeline requires contact access', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visibleStore = Store::factory()->create(['area_id' => $area->id]);
    $hiddenStore = Store::factory()->create(['area_id' => null]);

    $visibleContact = User::factory()->create();
    $hiddenContact = User::factory()->create();

    StoreOwner::query()->create([
        'store_id' => $visibleStore->id,
        'user_id' => $visibleContact->id,
        'role' => 'owner',
    ]);

    StoreOwner::query()->create([
        'store_id' => $hiddenStore->id,
        'user_id' => $hiddenContact->id,
        'role' => 'owner',
    ]);

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/contact/{$visibleContact->id}")
        ->assertOk();

    $this->actingAs($rep)
        ->getJson("/api/v1/activity/subjects/contact/{$hiddenContact->id}")
        ->assertForbidden();
});
test('out of scope store route is forbidden', function () {
    $rep = User::factory()->create();
    $rep->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
    $visible = Store::factory()->create(['area_id' => $area->id]);
    $hidden = Store::factory()->create(['area_id' => null]);

    $this->actingAs($rep)
        ->getJson("/api/v1/stores/{$visible->id}")
        ->assertOk();

    $this->actingAs($rep)
        ->getJson("/api/v1/stores/{$hidden->id}")
        ->assertForbidden();
});
test('duplicate ach trigger returns existing transfer', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $store = Store::factory()->create();
    $period = RoyaltyPeriod::query()->create([
        'store_id' => $store->id,
        'frequency' => 'monthly',
        'period_start' => now()->subMonth(),
        'period_end' => now(),
        'recorded_at' => now(),
        'total_royalties' => 150.00,
        'status' => 'open',
    ]);

    $first = $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/royalty-periods/{$period->id}/trigger-ach", [])
        ->assertCreated()
        ->json('data.id');

    $second = $this->actingAs($user)
        ->postJson("/api/v1/stores/{$store->id}/royalty-periods/{$period->id}/trigger-ach", [])
        ->assertCreated()
        ->json('data.id');

    expect($second)->toBe($first);
    expect(AchTransfer::query()->where('royalty_period_id', $period->id)->count())->toBe(1);
});
