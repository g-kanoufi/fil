<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AchPlaidLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_franchisor_can_complete_sandbox_plaid_link(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        AchCustomer::query()->create([
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
            'external_customer_id' => 'cust-test',
            'status' => 'active',
        ]);

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/plaid/complete", [
                'sandbox' => true,
                'account_name' => 'Sandbox checking',
            ])
            ->assertOk()
            ->assertJsonPath('data.status', 'linked')
            ->assertJsonPath('data.funding_source.name', 'Sandbox checking');

        $this->assertDatabaseHas('ach_funding_sources', [
            'name' => 'Sandbox checking',
            'status' => 'active',
        ]);
    }

    public function test_complete_plaid_link_requires_enrollment(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/plaid/complete", [
                'sandbox' => true,
            ])
            ->assertStatus(422)
            ->assertJsonPath('data.message', 'Store is not enrolled for ACH.');
    }

    public function test_link_token_includes_sandbox_mode_without_credentials(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $this->actingAs($user)
            ->postJson("/api/v1/stores/{$store->id}/ach/plaid/link-token")
            ->assertOk()
            ->assertJsonPath('data.mode', 'sandbox')
            ->assertJsonStructure(['data' => ['link_token', 'expiration', 'mode']]);
    }

    public function test_ach_customer_payload_includes_pending_accounts(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        $customer = AchCustomer::query()->create([
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
            'external_customer_id' => 'cust-test',
            'status' => 'active',
            'profile' => [
                'plaid_pending_verification_accounts' => [
                    [
                        'id' => 'acct-pending',
                        'name' => 'Pending checking',
                        'verification_status' => 'pending_manual_verification',
                    ],
                ],
            ],
        ]);

        AchFundingSource::query()->create([
            'ach_customer_id' => $customer->id,
            'external_funding_source_id' => 'fs-test',
            'name' => 'Operating',
            'type' => 'bank',
            'status' => 'active',
            'is_default' => true,
        ]);

        $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/ach/customer")
            ->assertOk()
            ->assertJsonPath('data.plaid_mode', 'sandbox')
            ->assertJsonPath('data.plaid_pending_verification_accounts.0.id', 'acct-pending');
    }
}
