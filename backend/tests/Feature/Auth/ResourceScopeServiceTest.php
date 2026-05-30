<?php

declare(strict_types=1);
use App\Models\Area;
use App\Models\Lead;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->scope = app(ResourceScopeService::class);
});
test('franchisee cannot list leads', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    expect($this->scope->mayListLeads($user))->toBeFalse();
});
test('franchisee sees only assigned stores', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    $visible = Store::factory()->create(['name' => 'Mine']);
    $hidden = Store::factory()->create(['name' => 'Other']);

    StoreOwner::query()->create([
        'store_id' => $visible->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    expect($this->scope->canViewStore($user, $visible))->toBeTrue();
    expect($this->scope->canViewStore($user, $hidden))->toBeFalse();
});
test('area rep sees leads in assigned area', function () {
    $user = User::factory()->create();
    $user->assignRole('area_rep');

    $area = Area::factory()->create(['extras' => ['rep_user_id' => $user->id]]);
    $inArea = Lead::factory()->create(['area_id' => $area->id, 'title' => 'In area']);
    $outside = Lead::factory()->create(['area_id' => null, 'title' => 'Outside']);

    expect($this->scope->canViewLead($user, $inArea))->toBeTrue();
    expect($this->scope->canViewLead($user, $outside))->toBeFalse();
});
test('franchisee grid query returns no leads', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');
    Lead::factory()->count(2)->create();

    $response = $this->actingAs($user)->postJson('/api/v1/query/leads', [
        'limit' => 10,
    ]);

    $response->assertForbidden();
});
test('franchisee grid query returns scoped stores', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    $visible = Store::factory()->create(['name' => 'Visible store']);
    Store::factory()->create(['name' => 'Hidden store']);

    StoreOwner::query()->create([
        'store_id' => $visible->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $response = $this->actingAs($user)->postJson('/api/v1/query/stores', [
        'limit' => 50,
    ]);

    $response->assertOk();
    $titles = collect($response->json('hits.hits'))->map(fn (array $hit) => $hit['_source']['post_title'] ?? null);

    expect($titles->contains('Visible store'))->toBeTrue();
    expect($titles->contains('Hidden store'))->toBeFalse();
});
test('staff gate returns machine code', function () {
    $user = User::factory()->create();
    $user->assignRole('prospect');

    $response = $this->actingAs($user)->getJson('/api/v1/session');

    $response->assertForbidden()
        ->assertJsonPath('code', 'staff_required');
});
