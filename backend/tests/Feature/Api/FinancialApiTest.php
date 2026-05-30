<?php

namespace Tests\Feature\Api;

use App\Models\AchTransfer;
use App\Models\PosConnection;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_calculate_and_list_royalty_periods(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create(['royalty_config' => ['default_rate' => 0.06]]);

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/royalty-periods/calculate", [
                'gross_revenue' => 10000,
                'order_count' => 120,
            ])
            ->assertCreated()
            ->assertJsonPath('data.gross_revenue', '10000.00')
            ->assertJsonPath('data.total_royalties', '600.00')
            ->assertJsonCount(1, 'data.line_items');

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/royalty-periods")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_franchisor_can_view_royalty_period_detail(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();
        $period = RoyaltyPeriod::query()->create([
            'store_id' => $store->id,
            'frequency' => 'weekly',
            'period_start' => now()->startOfWeek(),
            'period_end' => now()->endOfWeek(),
            'recorded_at' => now(),
            'gross_revenue' => 5000,
            'total_royalties' => 300,
            'status' => 'calculated',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/royalty-periods/{$period->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $period->id);
    }

    public function test_lead_owner_cannot_access_royalties(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/royalty-periods")
            ->assertForbidden();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/royalty-periods/calculate", [
                'gross_revenue' => 100,
            ])
            ->assertForbidden();
    }

    public function test_franchisor_can_list_ach_transfers(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $transfer = AchTransfer::query()->create([
            'store_id' => $store->id,
            'transferred_at' => now(),
            'provider' => 'dwolla',
            'status' => 1,
            'amount' => 600,
            'royalty_name' => 'Unit royalty',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/ach-transfers')
            ->assertOk()
            ->assertJsonPath('data.0.id', $transfer->id);

        $this->actingAs($user)
            ->getJson("/api/v1/ach-transfers/{$transfer->id}")
            ->assertOk()
            ->assertJsonPath('data.amount', '600.00');
    }

    public function test_franchisor_can_list_and_sync_pos_connections(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $connection = PosConnection::query()->create([
            'store_id' => $store->id,
            'provider' => 'square',
            'external_location_id' => 'LOC123',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/pos-connections")
            ->assertOk()
            ->assertJsonPath('data.0.provider', 'square');

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/pos-connections/{$connection->id}/sync")
            ->assertStatus(202)
            ->assertJsonPath('data.status', 'synced');
    }

    public function test_franchisor_can_trigger_ach_from_royalty_period(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $period = RoyaltyPeriod::query()->create([
            'store_id' => $store->id,
            'frequency' => 'weekly',
            'period_start' => now()->startOfWeek(),
            'period_end' => now()->endOfWeek(),
            'recorded_at' => now(),
            'gross_revenue' => 5000,
            'total_royalties' => 300,
            'status' => 'calculated',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/royalty-periods/{$period->id}/trigger-ach")
            ->assertCreated()
            ->assertJsonPath('data.amount', '300.00')
            ->assertJsonPath('data.store_id', $store->id);
    }

    public function test_lead_owner_cannot_access_ach_or_pos(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/ach-transfers')
            ->assertForbidden();

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/pos-connections")
            ->assertForbidden();
    }
}
