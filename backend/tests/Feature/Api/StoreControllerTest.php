<?php

namespace Tests\Feature\Api;

use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_create_and_update_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');

        $create = $this->actingAs($user)
            ->postJson('/api/v1/stores', [
                'name' => 'PrimeIV Scottsdale',
                'store_status' => 'open',
            ])
            ->assertCreated()
            ->assertJsonPath('data.name', 'PrimeIV Scottsdale');

        $storeId = $create->json('data.id');

        $this->actingAs($user)
            ->patchJson("/api/v1/stores/{$storeId}", [
                'store_status' => 'pending',
            ])
            ->assertOk()
            ->assertJsonPath('data.store_status', 'pending');
    }

    public function test_lead_owner_cannot_create_store(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        $this->actingAs($user)
            ->postJson('/api/v1/stores', ['name' => 'Denied Store'])
            ->assertForbidden();
    }

    public function test_lead_owner_cannot_list_stores(): void
    {
        $user = User::factory()->create();
        $user->assignRole('lead_owner');

        Store::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/v1/stores')
            ->assertForbidden();
    }
}
