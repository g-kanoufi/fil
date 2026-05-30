<?php

namespace Tests\Feature\Api;

use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreOwnerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_sync_store_owners(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $owner = User::factory()->create();
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->putJson("/api/v1/stores/{$store->id}/owners", [
                'owners' => [
                    ['user_id' => $owner->id, 'ownership_pct' => 50, 'role' => 'primary'],
                ],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.user_id', $owner->id)
            ->assertJsonPath('data.0.ownership_pct', '50.00');

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/owners")
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_franchisor_can_convert_lead_to_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $lead = Lead::factory()->create(['title' => 'Converted Lead']);

        $this->actingAs($user)
            ->postJson("/api/v1/leads/{$lead->id}/convert")
            ->assertCreated()
            ->assertJsonPath('data.name', 'Converted Lead');

        $lead->refresh();
        $this->assertSame('converted', $lead->status);
        $this->assertSame(1, Store::query()->count());
    }
}
