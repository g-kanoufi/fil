<?php

namespace Tests\Feature\Api;

use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_load_dashboard_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Lead::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.leads.total', 2)
            ->assertJsonStructure([
                'data' => [
                    'leads' => ['total', 'by_status', 'added_monthly'],
                    'stores' => ['total'],
                    'fdd_deliveries' => ['sent_30d', 'total', 'sent_monthly'],
                    'recent_leads',
                    'pipeline',
                    'chart_months',
                ],
            ]);
    }

    public function test_franchisee_can_load_scoped_dashboard_stats(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisee');

        $visible = \App\Models\Store::factory()->create(['name' => 'Visible store', 'status' => 'active']);
        \App\Models\Store::factory()->create(['name' => 'Hidden store', 'status' => 'active']);
        Lead::factory()->count(3)->create(['status' => 'active']);

        \App\Models\StoreOwner::query()->create([
            'store_id' => $visible->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->getJson('/api/v1/dashboard')
            ->assertOk()
            ->assertJsonPath('data.leads.total', 0)
            ->assertJsonPath('data.stores.total', 1);
    }
}
