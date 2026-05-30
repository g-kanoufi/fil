<?php

namespace Tests\Feature\Api;

use App\Models\Closing;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClosingControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_lead_owner_can_list_closings(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        Closing::factory()->count(2)->create();

        $this->actingAs($user)
            ->getJson('/api/v1/closings')
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    public function test_franchisor_can_view_closing_detail_with_workflow_metadata(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $closing = Closing::factory()->create([
            'status' => 'pending',
            'extras' => [
                'fees' => [
                    ['label' => 'Franchise fee', 'amount_cents' => 5000000],
                ],
            ],
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/closings/{$closing->id}")
            ->assertOk()
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.status_label', 'Pending')
            ->assertJsonPath('data.fee_total_cents', 5000000)
            ->assertJsonPath('data.allowed_status_transitions.0.key', 'scheduled');
    }

    public function test_franchisor_can_transition_closing_status(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $closing = Closing::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patchJson("/api/v1/closings/{$closing->id}", [
                'status' => 'scheduled',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'scheduled')
            ->assertJsonPath('data.status_label', 'Scheduled');

        $this->assertDatabaseHas('closings', [
            'id' => $closing->id,
            'status' => 'scheduled',
        ]);

        $this->assertDatabaseHas('activity_events', [
            'category' => 'closing',
            'action' => 'updated',
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $closing = Closing::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patchJson("/api/v1/closings/{$closing->id}", [
                'status' => 'completed',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_franchisor_can_update_fee_lines(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $closing = Closing::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patchJson("/api/v1/closings/{$closing->id}", [
                'fee_lines' => [
                    ['label' => 'Initial franchise fee', 'amount_cents' => 4500000],
                    ['label' => 'Training fee', 'amount_cents' => 250000],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.fee_total_cents', 4750000)
            ->assertJsonCount(2, 'data.fee_lines');

        $closing->refresh();

        $this->assertSame(
            [
                ['label' => 'Initial franchise fee', 'amount_cents' => 4500000],
                ['label' => 'Training fee', 'amount_cents' => 250000],
            ],
            $closing->extras['fees'] ?? null,
        );
    }

    public function test_view_only_user_cannot_update_closing(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo(['app.access', 'leads.view']);

        $closing = Closing::factory()->create(['status' => 'pending']);

        $this->actingAs($user)
            ->patchJson("/api/v1/closings/{$closing->id}", [
                'status' => 'scheduled',
            ])
            ->assertForbidden();
    }
}
