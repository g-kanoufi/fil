<?php

namespace Tests\Feature\Api;

use App\Models\Fdd;
use App\Models\FddDelivery;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FddSignatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_sign_fdd_delivery(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = Lead::factory()->create();
        $fdd = Fdd::query()->create(['type' => 'unit', 'title' => 'Unit FDD', 'status' => 'active']);
        $delivery = FddDelivery::query()->create([
            'fdd_id' => $fdd->id,
            'lead_id' => $lead->id,
            'sent_at' => now(),
            'status' => 'sent',
            'delivery_method' => 'email',
            'meta' => [],
        ]);

        \App\Models\Signature::query()->create([
            'lead_id' => $lead->id,
            'fdd_delivery_id' => $delivery->id,
            'signer_user_id' => $user->id,
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/fdd-deliveries/{$delivery->id}/sign", [
                'signed_name' => 'Jane Smith',
                'agree' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.status', 'signed');

        $this->assertDatabaseHas('fdd_deliveries', ['id' => $delivery->id, 'status' => 'signed']);
        $lead->refresh();
        $this->assertNotNull($lead->fdd_signed_at);
        $this->assertSame('waiting_period', $lead->lead_fdd_status);
        $this->assertNotNull($lead->waiting_period_ends_at);
    }
}
