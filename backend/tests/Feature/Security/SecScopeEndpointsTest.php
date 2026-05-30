<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\Area;
use App\Models\AchTransfer;
use App\Models\Closing;
use App\Models\Lead;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SecScopeEndpointsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_area_rep_leads_index_is_scoped(): void
    {
        $rep = User::factory()->create();
        $rep->assignRole('area_rep');

        $area = Area::factory()->create(['extras' => ['rep_user_id' => $rep->id]]);
        $visible = Lead::factory()->create(['area_id' => $area->id, 'title' => 'Visible lead']);
        Lead::factory()->create(['area_id' => null, 'title' => 'Hidden lead']);

        $response = $this->actingAs($rep)->getJson('/api/v1/leads')->assertOk();

        $titles = collect($response->json('data'))->pluck('title')->all();
        $this->assertContains('Visible lead', $titles);
        $this->assertNotContains('Hidden lead', $titles);
    }

    public function test_franchisee_store_index_is_scoped(): void
    {
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
        $this->assertContains('My store', $names);
        $this->assertNotContains('Other store', $names);
    }

    public function test_activity_subject_timeline_requires_lead_access(): void
    {
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
    }

    public function test_out_of_scope_store_route_is_forbidden(): void
    {
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
    }

    public function test_duplicate_ach_trigger_returns_existing_transfer(): void
    {
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

        $this->assertSame($first, $second);
        $this->assertSame(1, AchTransfer::query()->where('royalty_period_id', $period->id)->count());
    }
}
