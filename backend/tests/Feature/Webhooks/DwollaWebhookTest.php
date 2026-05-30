<?php

declare(strict_types=1);
use App\Models\AchCustomer;
use App\Models\AchTransfer;
use App\Models\Store;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;

uses(RefreshDatabase::class);

test('transfer webhook updates status when signed', function () {
    config(['services.dwolla.webhook_secret' => 'dwolla-secret']);

    $store = Store::factory()->create();
    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-123',
        'status' => 'active',
    ]);

    $transfer = AchTransfer::query()->create([
        'store_id' => $store->id,
        'external_transfer_id' => 'transfer-456',
        'amount' => 100.00,
        'provider_status' => 'pending',
        'status' => 0,
        'transferred_at' => now(),
    ]);

    $payload = [
        'id' => 'event-001',
        'topic' => 'transfer_completed',
        'resourceId' => 'transfer-456',
    ];

    postSignedDwollaWebhook($payload)->assertOk();

    expect($transfer->fresh()?->provider_status)->toBe('processed');
    $this->assertDatabaseHas('webhook_events', [
        'provider' => 'dwolla',
        'external_event_id' => 'event-001',
    ]);
});
test('customer webhook updates status when signed', function () {
    config(['services.dwolla.webhook_secret' => 'dwolla-secret']);

    $store = Store::factory()->create();
    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-789',
        'status' => 'unverified',
    ]);

    $payload = [
        'id' => 'event-002',
        'topic' => 'customer_verified',
        'resourceId' => 'cust-789',
    ];

    postSignedDwollaWebhook($payload)->assertOk();

    expect($customer->fresh()?->status)->toBe('verified');
});
test('webhook rejects invalid signature when secret configured', function () {
    config(['services.dwolla.webhook_secret' => 'dwolla-secret']);

    $body = json_encode([
        'id' => 'event-003',
        'topic' => 'transfer_completed',
        'resourceId' => 'transfer-999',
    ], JSON_THROW_ON_ERROR);

    $this->call(
        'POST',
        '/api/webhooks/dwolla',
        [],
        [],
        [],
        [
            'HTTP_X-Request-Signature-SHA-256' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        $body,
    )->assertForbidden();
});
test('webhook rejects replayed event', function () {
    config(['services.dwolla.webhook_secret' => 'dwolla-secret']);

    WebhookEvent::query()->create([
        'provider' => 'dwolla',
        'external_event_id' => 'event-replay',
    ]);

    $payload = [
        'id' => 'event-replay',
        'topic' => 'transfer_completed',
        'resourceId' => 'transfer-replay',
    ];

    postSignedDwollaWebhook($payload)->assertConflict();
});
/**
 * @param  array<string, mixed>  $payload
 */
function postSignedDwollaWebhook(array $payload): TestResponse
{
    $body = json_encode($payload, JSON_THROW_ON_ERROR);
    $signature = hash_hmac('sha256', $body, 'dwolla-secret');

    return test()->call(
        'POST',
        '/api/webhooks/dwolla',
        [],
        [],
        [],
        [
            'HTTP_X-Request-Signature-SHA-256' => $signature,
            'CONTENT_TYPE' => 'application/json',
            'HTTP_ACCEPT' => 'application/json',
        ],
        $body,
    );
}
