<?php

namespace Tests\Feature\Api;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CommunicationControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_list_communications_for_lead(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = Lead::factory()->create();

        Communication::query()->create([
            'lead_id' => $lead->id,
            'type' => 'email',
            'direction' => 'outbound',
            'message' => 'Hello prospect',
            'status' => 'sent',
            'sent_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/communications?lead_id={$lead->id}")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.message', 'Hello prospect');
    }

    public function test_lead_owner_can_list_communications(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->getJson('/api/v1/communications')
            ->assertOk();
    }
}
