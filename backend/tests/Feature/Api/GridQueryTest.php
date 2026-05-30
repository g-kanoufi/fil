<?php

use App\Models\Area;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('lead owner can query leads grid with aggregations', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Lead::factory()->count(3)->create([
        'lead_fdd_status' => 'active',
        'owner_user_id' => $user->id,
    ]);

    $this->actingAs($user)
        ->postJson('/api/v1/query/leads', [
            'limit' => 10,
            'include_aggregations' => true,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'hits' => ['total' => ['value'], 'hits'],
            'aggregations' => [
                'meta.lead_status' => ['buckets'],
            ],
            'meta' => ['next_cursor'],
        ])
        ->assertJsonPath('hits.total.value', 3);
});

test('lead grid cursor returns next page without overlap', function () {
    $user = User::factory()->create();
    $user->assignRole('lead_owner');

    Lead::factory()->count(60)->create([
        'lead_fdd_status' => 'active',
        'owner_user_id' => $user->id,
    ]);

    $first = $this->actingAs($user)
        ->postJson('/api/v1/query/leads', ['limit' => 25])
        ->assertOk()
        ->json();

    $cursor = $first['meta']['next_cursor'];
    expect($cursor)->not->toBeNull();

    $second = $this->actingAs($user)
        ->postJson('/api/v1/query/leads', [
            'limit' => 25,
            'cursor' => $cursor,
            'include_aggregations' => false,
        ])
        ->assertOk()
        ->json();

    $firstIds = array_column(array_column($first['hits']['hits'], '_source'), 'id');
    $secondIds = array_column(array_column($second['hits']['hits'], '_source'), 'id');

    expect(array_values(array_intersect($firstIds, $secondIds)))->toBe([]);
    expect($secondIds)->toHaveCount(25);
});

test('unknown resource returns 404', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    $this->actingAs($user)
        ->postJson('/api/v1/query/unknown')
        ->assertNotFound();
});

test('franchisor can query contacts grid', function () {
    $user = User::factory()->create(['name' => 'Staff User']);
    $user->assignRole('franchisor');

    $contact = User::factory()->create(['name' => 'Contact One']);
    $contact->assignRole('lead_owner');

    $this->actingAs($user)
        ->postJson('/api/v1/query/contacts', [
            'limit' => 10,
            'include_aggregations' => true,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'hits' => ['total' => ['value'], 'hits'],
            'aggregations' => [
                'meta.contacts' => ['buckets'],
            ],
        ])
        ->assertJsonPath('hits.total.value', 2);
});

test('leads active filter returns non inactive leads', function () {
    $user = User::factory()->create();
    $user->assignRole('admin');

    Lead::factory()->create(['lead_fdd_status' => 'active', 'title' => 'Active Lead']);
    Lead::factory()->create(['lead_fdd_status' => 'disclosed', 'title' => 'Disclosed Lead']);
    Lead::factory()->create(['lead_fdd_status' => 'inactive', 'title' => 'Inactive Lead']);

    $this->actingAs($user)
        ->postJson('/api/v1/query/leads', [
            'limit' => 50,
            'filters' => [
                'lead_fdd_status' => ['active', 'disclosed', 'Active', 'FDD Sent'],
            ],
        ])
        ->assertOk()
        ->assertJsonPath('hits.total.value', 2);
});

test('franchisor can query stores grid', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    Store::factory()->count(2)->create(['store_status' => 'open']);

    $this->actingAs($user)
        ->postJson('/api/v1/query/stores', [
            'limit' => 10,
            'include_aggregations' => true,
        ])
        ->assertOk()
        ->assertJsonStructure([
            'hits' => ['total' => ['value'], 'hits'],
            'aggregations' => [
                'meta.stores' => ['buckets'],
                'meta.areas' => ['buckets'],
            ],
        ])
        ->assertJsonPath('hits.total.value', 2);
});

test('store area filter returns stores for area', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    $areaA = Area::factory()->create(['name' => 'North Region']);
    $areaB = Area::factory()->create(['name' => 'South Region']);

    Store::factory()->create(['area_id' => $areaA->id, 'store_status' => 'open', 'name' => 'Store A']);
    Store::factory()->create(['area_id' => $areaB->id, 'store_status' => 'open', 'name' => 'Store B']);
    Store::factory()->create(['area_id' => null, 'store_status' => 'open', 'name' => 'Unassigned']);

    $response = $this->actingAs($user)
        ->postJson('/api/v1/query/stores', [
            'limit' => 50,
            'include_aggregations' => true,
            'filters' => ['area_id' => (string) $areaA->id],
        ])
        ->assertOk()
        ->json();

    expect($response['hits']['total']['value'])->toBe(1);

    $areaBuckets = collect($response['aggregations']['meta.areas']['buckets'] ?? []);
    $north = $areaBuckets->firstWhere('key', (string) $areaA->id);
    expect($north)->not->toBeNull();
    expect($north['label'])->toBe('North Region');
    expect($north['doc_count'])->toBe(1);

    $hit = $response['hits']['hits'][0]['_source']['meta'] ?? [];
    expect($hit['areas'] ?? null)->toBe('North Region');
});
