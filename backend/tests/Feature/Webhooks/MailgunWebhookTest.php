<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\Communication;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MailgunWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_updates_delivery_status_when_signed(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'signing-key']);

        $rule = NotificationRule::query()->create([
            'hash' => 'webhook-rule',
            'title' => 'Webhook test',
            'trigger_slug' => 'lead.created',
            'enabled' => true,
        ]);

        $delivery = NotificationDelivery::query()->create([
            'notification_rule_id' => $rule->id,
            'trigger_slug' => 'lead.created',
            'channel' => 'email',
            'status' => 'sent',
            'recipient_email' => 'prospect@example.com',
            'subject' => 'Hello',
            'provider' => 'mailgun',
            'provider_message_id' => 'abc123',
        ]);

        $timestamp = (string) time();
        $token = 'token-value';
        $signature = hash_hmac('sha256', $timestamp.$token, 'signing-key');

        $this->postJson('/api/webhooks/mailgun', [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
            'event-data' => [
                'event' => 'delivered',
                'message' => [
                    'headers' => [
                        'message-id' => 'abc123',
                        'x-fil-delivery-id' => (string) $delivery->id,
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame('delivered', $delivery->fresh()?->status);
    }

    public function test_webhook_updates_communication_status_by_message_id(): void
    {
        $communication = Communication::query()->create([
            'type' => 'email',
            'direction' => 'outbound',
            'message' => 'Hello',
            'provider' => 'mailgun',
            'external_message_id' => 'comm-123',
            'status' => 'sent',
        ]);

        $this->postJson('/api/webhooks/mailgun', [
            'event-data' => [
                'event' => 'failed',
                'reason' => 'Bounced',
                'message' => [
                    'headers' => [
                        'message-id' => 'comm-123',
                    ],
                ],
            ],
        ])->assertOk();

        $this->assertSame('failed', $communication->fresh()?->status);
        $this->assertSame('Bounced', $communication->fresh()?->errors);
    }

    public function test_failed_event_suppresses_recipient_email(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'signing-key']);

        $timestamp = (string) time();
        $token = 'token-value';
        $signature = hash_hmac('sha256', $timestamp.$token, 'signing-key');

        $this->postJson('/api/webhooks/mailgun', [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
            'event-data' => [
                'event' => 'failed',
                'recipient' => 'bounced@example.com',
                'reason' => 'Bounced',
            ],
        ])->assertOk();

        $this->assertDatabaseHas('communication_suppressions', [
            'channel' => 'email',
            'address' => 'bounced@example.com',
            'reason' => 'bounce',
        ]);
    }
}
