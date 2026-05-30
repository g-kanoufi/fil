<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GridQueryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lead_owner_can_query_leads_grid_with_aggregations(): void
    {
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
    }

    public function test_lead_grid_cursor_returns_next_page_without_overlap(): void
    {
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
        $this->assertNotNull($cursor);

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

        $this->assertSame([], array_values(array_intersect($firstIds, $secondIds)));
        $this->assertCount(25, $secondIds);
    }

    public function test_unknown_resource_returns_404(): void
    {
        $user = User::factory()->create();
        $user->assignRole('admin');

        $this->actingAs($user)
            ->postJson('/api/v1/query/unknown')
            ->assertNotFound();
    }

    public function test_franchisor_can_query_contacts_grid(): void
    {
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
    }

    public function test_leads_active_filter_returns_non_inactive_leads(): void
    {
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
    }

    public function test_franchisor_can_query_stores_grid(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        \App\Models\Store::factory()->count(2)->create(['store_status' => 'open']);

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
    }

    public function test_store_area_filter_returns_stores_for_area(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $areaA = \App\Models\Area::factory()->create(['name' => 'North Region']);
        $areaB = \App\Models\Area::factory()->create(['name' => 'South Region']);

        \App\Models\Store::factory()->create(['area_id' => $areaA->id, 'store_status' => 'open', 'name' => 'Store A']);
        \App\Models\Store::factory()->create(['area_id' => $areaB->id, 'store_status' => 'open', 'name' => 'Store B']);
        \App\Models\Store::factory()->create(['area_id' => null, 'store_status' => 'open', 'name' => 'Unassigned']);

        $response = $this->actingAs($user)
            ->postJson('/api/v1/query/stores', [
                'limit' => 50,
                'include_aggregations' => true,
                'filters' => ['area_id' => (string) $areaA->id],
            ])
            ->assertOk()
            ->json();

        $this->assertSame(1, $response['hits']['total']['value']);

        $areaBuckets = collect($response['aggregations']['meta.areas']['buckets'] ?? []);
        $north = $areaBuckets->firstWhere('key', (string) $areaA->id);
        $this->assertNotNull($north);
        $this->assertSame('North Region', $north['label']);
        $this->assertSame(1, $north['doc_count']);

        $hit = $response['hits']['hits'][0]['_source']['meta'] ?? [];
        $this->assertSame('North Region', $hit['areas'] ?? null);
    }
}
