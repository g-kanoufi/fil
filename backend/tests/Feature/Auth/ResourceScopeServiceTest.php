<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\Area;
use App\Models\Lead;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ResourceScopeServiceTest extends TestCase
{
    use RefreshDatabase;

    private ResourceScopeService $scope;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
        $this->scope = app(ResourceScopeService::class);
    }

    public function test_franchisee_cannot_list_leads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisee');

        $this->assertFalse($this->scope->mayListLeads($user));
    }

    public function test_franchisee_sees_only_assigned_stores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisee');

        $visible = Store::factory()->create(['name' => 'Mine']);
        $hidden = Store::factory()->create(['name' => 'Other']);

        StoreOwner::query()->create([
            'store_id' => $visible->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->assertTrue($this->scope->canViewStore($user, $visible));
        $this->assertFalse($this->scope->canViewStore($user, $hidden));
    }

    public function test_area_rep_sees_leads_in_assigned_area(): void
    {
        $user = User::factory()->create();
        $user->assignRole('area_rep');

        $area = Area::factory()->create(['extras' => ['rep_user_id' => $user->id]]);
        $inArea = Lead::factory()->create(['area_id' => $area->id, 'title' => 'In area']);
        $outside = Lead::factory()->create(['area_id' => null, 'title' => 'Outside']);

        $this->assertTrue($this->scope->canViewLead($user, $inArea));
        $this->assertFalse($this->scope->canViewLead($user, $outside));
    }

    public function test_franchisee_grid_query_returns_no_leads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisee');
        Lead::factory()->count(2)->create();

        $response = $this->actingAs($user)->postJson('/api/v1/query/leads', [
            'limit' => 10,
        ]);

        $response->assertForbidden();
    }

    public function test_franchisee_grid_query_returns_scoped_stores(): void
    {
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

        $this->assertTrue($titles->contains('Visible store'));
        $this->assertFalse($titles->contains('Hidden store'));
    }

    public function test_staff_gate_returns_machine_code(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $response = $this->actingAs($user)->getJson('/api/v1/session');

        $response->assertForbidden()
            ->assertJsonPath('code', 'staff_required');
    }
}
