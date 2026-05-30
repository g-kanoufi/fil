<?php

declare(strict_types=1);

namespace Tests\Feature\Security;

use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    protected function tearDown(): void
    {
        app()->detectEnvironment(fn (): string => 'testing');

        parent::tearDown();
    }

    public function test_staff_login_is_throttled_in_staging(): void
    {
        app()->detectEnvironment(fn (): string => 'staging');

        User::factory()->create([
            'email' => 'throttle@fil.test',
            'password' => \Illuminate\Support\Facades\Hash::make('secret'),
        ])->assignRole('lead_owner');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->postJson('/api/v1/session', [
                'email' => 'throttle@fil.test',
                'password' => 'wrong',
            ]);
        }

        $this->postJson('/api/v1/session', [
            'email' => 'throttle@fil.test',
            'password' => 'wrong',
        ])->assertStatus(429);
    }

    public function test_ach_customer_profile_is_encrypted_at_rest(): void
    {
        $store = Store::factory()->create();

        AchCustomer::query()->create([
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
            'external_customer_id' => 'cust-encrypt',
            'status' => 'active',
            'profile' => [
                'plaid_access_token' => 'access-secret-token',
                'plaid_item_id' => 'item-secret',
            ],
        ]);

        $rawProfile = DB::table('ach_customers')->value('profile');

        $this->assertIsString($rawProfile);
        $this->assertStringNotContainsString('access-secret-token', $rawProfile);
        $this->assertStringNotContainsString('item-secret', $rawProfile);

        $customer = AchCustomer::query()->first();
        $this->assertSame('access-secret-token', $customer?->profile['plaid_access_token'] ?? null);
    }

    public function test_ach_customer_api_never_exposes_plaid_access_token(): void
    {
        $user = User::factory()->create();
        $user->assignRole('franchisor');
        $store = Store::factory()->create();

        AchCustomer::query()->create([
            'owner_type' => Store::class,
            'owner_id' => $store->id,
            'provider' => 'dwolla',
            'external_customer_id' => 'cust-api',
            'status' => 'active',
            'profile' => [
                'plaid_access_token' => 'access-secret-token',
            ],
        ]);

        $response = $this->actingAs($user)
            ->getJson("/api/v1/stores/{$store->id}/ach/customer")
            ->assertOk();

        $encoded = json_encode($response->json(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('access-secret-token', $encoded);
    }

    public function test_embed_intake_rejects_any_key_when_allowlist_empty_in_production(): void
    {
        config(['fil.embed.site_keys' => []]);
        app()->detectEnvironment(fn (): string => 'production');

        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_any_client_key',
            'first_name' => 'Test',
            'last_name' => 'Lead',
            'email' => 'test@example.com',
        ])->assertForbidden();
    }

    public function test_embed_intake_accepts_allowlisted_key_in_production(): void
    {
        config(['fil.embed.site_keys' => ['pk_client_prod']]);
        app()->detectEnvironment(fn (): string => 'production');

        $this->postJson('/api/public/v1/leads', [
            'site_key' => 'pk_client_prod',
            'first_name' => 'Test',
            'last_name' => 'Lead',
            'email' => 'test@example.com',
        ])->assertCreated();
    }
}
