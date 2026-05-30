<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadPipelineTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_staff_can_transition_lead_phase(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');
        $lead = Lead::factory()->create(['pipeline_phase' => 1, 'owner_user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson("/api/v1/leads/{$lead->id}/transition-phase", ['to_phase' => 2])
            ->assertOk()
            ->assertJsonPath('data.pipeline_phase', 2)
            ->assertJsonPath('data.pipeline_phase_label', 'Outreach');

        $this->assertDatabaseHas('lead_phase_events', [
            'lead_id' => $lead->id,
            'from_phase' => 1,
            'to_phase' => 2,
            'actor_user_id' => $user->id,
        ]);
    }

    public function test_staff_can_update_lead_fields(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $lead = Lead::factory()->create();

        $this->actingAs($user)
            ->patchJson("/api/v1/leads/{$lead->id}", [
                'lead_temp' => 'hot',
                'lead_source' => 'referral',
            ])
            ->assertOk()
            ->assertJsonPath('data.lead_temp', 'hot')
            ->assertJsonPath('data.lead_source', 'referral');
    }
}
