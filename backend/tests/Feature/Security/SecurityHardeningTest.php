<?php

declare(strict_types=1);
use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use App\Models\WidgetForm;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
afterEach(function () {
    app()->detectEnvironment(fn (): string => 'testing');

});
test('staff login is throttled in staging', function () {
    app()->detectEnvironment(fn (): string => 'staging');

    User::factory()->create([
        'email' => 'throttle@fil.test',
        'password' => Hash::make('secret'),
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
});
test('ach customer profile is encrypted at rest', function () {
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

    expect($rawProfile)->toBeString();
    $this->assertStringNotContainsString('access-secret-token', $rawProfile);
    $this->assertStringNotContainsString('item-secret', $rawProfile);

    $customer = AchCustomer::query()->first();
    expect($customer?->profile['plaid_access_token'] ?? null)->toBe('access-secret-token');
});
test('ach customer api never exposes plaid access token', function () {
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
});
test('embed intake rejects any key when allowlist empty in production', function () {
    config(['fil.embed.site_keys' => []]);
    app()->detectEnvironment(fn (): string => 'production');

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_any_client_key',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'test@example.com',
    ], ['Origin' => 'https://client.example.com'])->assertForbidden();
});
test('embed intake accepts allowlisted key in production', function () {
    config([
        'fil.embed.site_keys' => ['pk_client_prod'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'app.url' => 'https://crm.example.com',
    ]);
    app()->detectEnvironment(fn (): string => 'production');

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_client_prod',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'test@example.com',
    ], ['Origin' => 'https://client.example.com'])->assertCreated();
});
test('embed intake rejects disallowed origin in production', function () {
    config([
        'fil.embed.site_keys' => ['pk_client_prod'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'app.url' => 'https://crm.example.com',
    ]);
    app()->detectEnvironment(fn (): string => 'production');

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_client_prod',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'test@example.com',
    ], ['Origin' => 'https://evil.example.com'])
        ->assertForbidden()
        ->assertJsonPath('message', 'Origin not allowed.');
});
test('embed intake rejects missing origin when allowlist required in production', function () {
    config([
        'fil.embed.site_keys' => ['pk_client_prod'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'app.url' => 'https://crm.example.com',
    ]);
    app()->detectEnvironment(fn (): string => 'production');

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_client_prod',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'test@example.com',
    ])->assertForbidden()
        ->assertJsonPath('message', 'Origin not allowed.');
});
test('embed intake accepts active widget form site key without env entry', function () {
    config([
        'fil.embed.site_keys' => [],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'app.url' => 'https://crm.example.com',
    ]);
    app()->detectEnvironment(fn (): string => 'production');

    WidgetForm::query()->create([
        'key' => 'lead_short',
        'name' => 'Short',
        'site_key' => 'pk_live_fromdatabaseonly123456',
        'status' => 'active',
    ]);

    $this->postJson('/api/public/v1/leads', [
        'site_key' => 'pk_live_fromdatabaseonly123456',
        'first_name' => 'Test',
        'last_name' => 'Lead',
        'email' => 'test@example.com',
    ], ['Origin' => 'https://client.example.com'])->assertCreated();
});
test('security headers include content security policy in staging', function () {
    config(['fil-security.csp.enabled' => true]);
    app()->detectEnvironment(fn (): string => 'staging');

    $response = $this->get('/up');

    $response->assertOk();
    expect($response->headers->has('Content-Security-Policy'))->toBeTrue();
    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
    expect($response->headers->get('Content-Security-Policy'))->toContain('cdn.plaid.com');
});
test('public lead intake is throttled per site key in staging', function () {
    config([
        'fil.embed.site_keys' => ['pk_rate_test'],
        'fil.embed_allowed_origins' => ['https://client.example.com'],
        'fil-security.public_lead_intake.site_key_max_attempts' => 3,
        'app.url' => 'https://crm.example.com',
    ]);
    app()->detectEnvironment(fn (): string => 'staging');

    $payload = [
        'site_key' => 'pk_rate_test',
        'first_name' => 'Rate',
        'last_name' => 'Limit',
    ];

    for ($attempt = 0; $attempt < 3; $attempt++) {
        $this->postJson('/api/public/v1/leads', [
            ...$payload,
            'email' => "rate-limit-{$attempt}@example.com",
        ], ['Origin' => 'https://client.example.com'])->assertCreated();
    }

    $this->postJson('/api/public/v1/leads', [
        ...$payload,
        'email' => 'rate-limit-final@example.com',
    ], ['Origin' => 'https://client.example.com'])->assertStatus(429);
});
