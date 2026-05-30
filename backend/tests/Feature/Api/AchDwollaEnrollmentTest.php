<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AchDwollaEnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_sandbox_enroll_store_for_ach(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [
                'sandbox' => true,
            ])
            ->assertCreated()
            ->assertJsonPath('data.enrolled', true)
            ->assertJsonStructure(['data' => ['customer' => ['external_customer_id', 'status']]]);

        $this->assertDatabaseHas('ach_customers', [
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
        ]);
    }

    public function test_enroll_requires_external_customer_id_in_live_mode(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [])
            ->assertStatus(422)
            ->assertJsonPath('data.message', 'external_customer_id is required.');
    }

    public function test_live_enroll_requires_dwolla_client_token_session(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/enroll", [
                'external_customer_id' => 'cust-unverified',
            ])
            ->assertStatus(422)
            ->assertJsonPath('data.message', 'Request a Dwolla client token before enrolling.');
    }

    public function test_customer_payload_includes_dwolla_config(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/ach/customer")
            ->assertOk()
            ->assertJsonPath('data.enrolled', false)
            ->assertJsonPath('data.dwolla_mode', 'sandbox')
            ->assertJsonPath('data.dwolla_environment', 'sandbox')
            ->assertJsonStructure(['data' => ['terms_url', 'privacy_url']]);
    }

    public function test_dwolla_client_token_rejects_when_not_configured(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/client-token", [
                'action' => 'customer.create',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Dwolla is not configured.');
    }

    public function test_certify_ownership_requires_enrollment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/certify-ownership")
            ->assertStatus(422)
            ->assertJsonPath('data.message', 'Store is not enrolled for ACH.');
    }

    public function test_certify_ownership_marks_profile_in_sandbox(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        AchCustomer::query()->create([
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
            'external_customer_id' => 'sandbox-cust-test',
            'status' => 'verified',
            'profile' => ['sandbox' => true],
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/dwolla/certify-ownership")
            ->assertOk()
            ->assertJsonPath('data.ownership_certified', true);

        $profile = AchCustomer::query()->first()?->profile;
        $this->assertTrue(is_array($profile));
        $this->assertTrue($profile['ownership_certified'] ?? false);
    }
}
