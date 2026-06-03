<?php

declare(strict_types=1);
use App\Models\Communication;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('webhook updates delivery status when signed', function () {
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

    expect($delivery->fresh()?->status)->toBe('delivered');
});
test('webhook updates communication status by message id', function () {
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

    expect($communication->fresh()?->status)->toBe('failed');
    expect($communication->fresh()?->errors)->toBe('Bounced');

    $this->assertDatabaseHas('activity_events', [
        'category' => 'comm',
        'action' => 'failed',
        'source' => 'mailgun_webhook',
    ]);
});

test('delivered event records comm activity', function () {
    $communication = Communication::query()->create([
        'type' => 'email',
        'direction' => 'outbound',
        'message' => 'Hello',
        'provider' => 'mailgun',
        'external_message_id' => 'delivered-123',
        'status' => 'sent',
        'recipient_name' => 'Pat Prospect',
    ]);

    $this->postJson('/api/webhooks/mailgun', [
        'event-data' => [
            'id' => 'evt-delivered-1',
            'event' => 'delivered',
            'message' => [
                'headers' => [
                    'message-id' => 'delivered-123',
                ],
            ],
        ],
    ])->assertOk();

    expect($communication->fresh()?->status)->toBe('delivered');

    $this->assertDatabaseHas('activity_events', [
        'category' => 'comm',
        'action' => 'delivered',
        'source' => 'mailgun_webhook',
        'summary' => 'EMAIL delivered to Pat Prospect',
    ]);
});

test('opened event records comm activity without changing status', function () {
    $communication = Communication::query()->create([
        'type' => 'email',
        'direction' => 'outbound',
        'message' => 'Hello',
        'provider' => 'mailgun',
        'external_message_id' => 'open-123',
        'status' => 'delivered',
        'recipient_name' => 'Pat Prospect',
    ]);

    $this->postJson('/api/webhooks/mailgun', [
        'event-data' => [
            'id' => 'evt-open-1',
            'event' => 'opened',
            'message' => [
                'headers' => [
                    'message-id' => 'open-123',
                ],
            ],
        ],
    ])->assertOk();

    expect($communication->fresh()?->status)->toBe('delivered');
    expect($communication->fresh()?->meta['opened_at'] ?? null)->not->toBeNull();

    $this->assertDatabaseHas('activity_events', [
        'category' => 'comm',
        'action' => 'opened',
        'source' => 'mailgun_webhook',
    ]);
});
test('failed event suppresses recipient email', function () {
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
});
