<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\AchCustomer;
use App\Models\AchTransfer;
use App\Models\Store;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class DwollaWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_transfer_webhook_updates_status_when_signed(): void
    {
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

        $this->postSignedDwollaWebhook($payload)->assertOk();

        $this->assertSame('processed', $transfer->fresh()?->provider_status);
        $this->assertDatabaseHas('webhook_events', [
            'provider' => 'dwolla',
            'external_event_id' => 'event-001',
        ]);
    }

    public function test_customer_webhook_updates_status_when_signed(): void
    {
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

        $this->postSignedDwollaWebhook($payload)->assertOk();

        $this->assertSame('verified', $customer->fresh()?->status);
    }

    public function test_webhook_rejects_invalid_signature_when_secret_configured(): void
    {
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
    }

    public function test_webhook_rejects_replayed_event(): void
    {
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

        $this->postSignedDwollaWebhook($payload)->assertConflict();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function postSignedDwollaWebhook(array $payload): \Illuminate\Testing\TestResponse
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $signature = hash_hmac('sha256', $body, 'dwolla-secret');

        return $this->call(
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
}
