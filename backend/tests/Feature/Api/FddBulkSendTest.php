<?php

namespace Tests\Feature\Api;

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FddBulkSendTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_bulk_send_unit_fdd(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $prospect = User::factory()->create(['email' => 'prospect@example.com']);
        $prospect->assignRole('prospect');

        $area = Area::factory()->create();
        $lead = Lead::factory()->create([
            'prospect_user_id' => $prospect->id,
            'area_id' => $area->id,
        ]);

        Fdd::query()->create([
            'type' => 'unit',
            'title' => 'Unit FDD',
            'area_id' => $area->id,
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson('/api/v1/fdds/bulk-send', [
                'lead_ids' => [$lead->id],
                'type' => 'unit',
            ])
            ->assertCreated()
            ->assertJsonPath('data.sent.0.lead_id', $lead->id);

        $this->assertDatabaseHas('fdd_deliveries', [
            'lead_id' => $lead->id,
            'status' => 'sent',
        ]);

        $lead->refresh();
        $this->assertSame('disclosed', $lead->lead_fdd_status);
    }

    public function test_summary_endpoint_returns_counts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        Fdd::query()->create(['type' => 'unit', 'title' => 'Unit', 'status' => 'active']);

        $this->actingAs($user)
            ->getJson('/api/v1/fdds/summary')
            ->assertOk()
            ->assertJsonPath('data.fdds.total', 1);
    }
}
