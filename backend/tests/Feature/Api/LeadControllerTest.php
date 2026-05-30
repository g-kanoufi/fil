<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lead_owner_can_list_leads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');
        Lead::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/leads')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_franchisor_without_leads_permission_still_has_leads_view(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $this->actingAs($user)
            ->getJson('/api/v1/leads')
            ->assertOk();
    }

    public function test_prospect_cannot_list_leads(): void
    {
        $user = User::factory()->create();
        $user->assignRole('prospect');

        $this->actingAs($user)
            ->getJson('/api/v1/leads')
            ->assertForbidden();
    }

    public function test_franchisor_can_show_lead_with_area_and_pipeline_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $area = \App\Models\Area::factory()->create(['name' => 'North Region']);
        $lead = Lead::factory()->create([
            'title' => 'Show Me Lead',
            'area_id' => $area->id,
            'lead_fdd_status' => 'active',
            'pipeline_phase' => 2,
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/leads/{$lead->id}")
            ->assertOk()
            ->assertJsonPath('data.id', $lead->id)
            ->assertJsonPath('data.title', 'Show Me Lead')
            ->assertJsonPath('data.area_id', $area->id)
            ->assertJsonPath('data.pipeline_phase', 2)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'title',
                    'pipeline_phase',
                    'pipeline_phase_label',
                    'application_status_label',
                    'area_id',
                    'phase_events',
                ],
            ]);
    }

    public function test_prospect_cannot_show_lead(): void
    {
        $prospect = User::factory()->create();
        $prospect->assignRole('prospect');

        $lead = Lead::factory()->create();

        $this->actingAs($prospect)
            ->getJson("/api/v1/leads/{$lead->id}")
            ->assertForbidden();
    }
}
