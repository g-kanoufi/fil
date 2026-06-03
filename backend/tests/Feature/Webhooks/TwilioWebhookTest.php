<?php

declare(strict_types=1);
use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\TwilioSignatureVerifier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});
test('inbound sms creates communication', function () {
    $this->postJson('/api/webhooks/twilio/inbound', [
        'From' => '+15551234567',
        'Body' => 'Reply from prospect',
        'MessageSid' => 'SM123',
    ])->assertNoContent();

    $this->assertDatabaseHas('communications', [
        'external_message_id' => 'SM123',
        'direction' => 'inbound',
        'message' => 'Reply from prospect',
    ]);

    expect(Communication::query()->count())->toBe(1);
});
test('inbound sms links matching lead', function () {
    $prospect = User::factory()->create(['phone' => '+15559876543']);
    $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

    $this->postJson('/api/webhooks/twilio/inbound', [
        'From' => '+15559876543',
        'Body' => 'Yes, interested',
        'MessageSid' => 'SM456',
    ])->assertNoContent();

    $this->assertDatabaseHas('communications', [
        'lead_id' => $lead->id,
        'recipient_user_id' => $prospect->id,
        'external_message_id' => 'SM456',
    ]);
});
test('inbound sms rejects invalid signature when token configured', function () {
    config(['services.twilio.token' => 'twilio-auth-token']);

    $this->postJson('/api/webhooks/twilio/inbound', [
        'From' => '+15551234567',
        'Body' => 'Forged message',
        'MessageSid' => 'SM999',
    ], [
        'X-Twilio-Signature' => 'invalid',
    ])->assertForbidden();

    $this->assertDatabaseMissing('communications', [
        'external_message_id' => 'SM999',
    ]);
});
test('inbound sms accepts valid signature when token configured', function () {
    config(['services.twilio.token' => 'twilio-auth-token']);

    $params = [
        'From' => '+15551234567',
        'Body' => 'Signed reply',
        'MessageSid' => 'SM777',
    ];

    $url = url('/api/webhooks/twilio/inbound');
    $signature = app(TwilioSignatureVerifier::class)->computeSignature($url, $params, 'twilio-auth-token');

    $this->postJson('/api/webhooks/twilio/inbound', $params, [
        'X-Twilio-Signature' => $signature,
    ])->assertNoContent();

    $this->assertDatabaseHas('communications', [
        'external_message_id' => 'SM777',
        'message' => 'Signed reply',
    ]);
});

test('status callback updates communication and records activity', function () {
    $communication = Communication::query()->create([
        'type' => 'sms',
        'direction' => 'outbound',
        'message' => 'Follow up',
        'provider' => 'twilio',
        'external_message_id' => 'SM-DELIVERED',
        'status' => 'sent',
        'recipient_name' => 'Pat Prospect',
    ]);

    $this->postJson('/api/webhooks/twilio/status', [
        'MessageSid' => 'SM-DELIVERED',
        'MessageStatus' => 'delivered',
    ])->assertNoContent();

    expect($communication->fresh()?->status)->toBe('delivered');

    $this->assertDatabaseHas('activity_events', [
        'category' => 'comm',
        'action' => 'delivered',
        'source' => 'twilio_webhook',
    ]);
});

test('status read records comm activity', function () {
    $communication = Communication::query()->create([
        'type' => 'sms',
        'direction' => 'outbound',
        'message' => 'Follow up',
        'provider' => 'twilio',
        'external_message_id' => 'SM-READ',
        'status' => 'delivered',
        'recipient_name' => 'Pat Prospect',
    ]);

    $this->postJson('/api/webhooks/twilio/status', [
        'MessageSid' => 'SM-READ',
        'MessageStatus' => 'read',
    ])->assertNoContent();

    expect($communication->fresh()?->status)->toBe('read');

    $this->assertDatabaseHas('activity_events', [
        'category' => 'comm',
        'action' => 'read',
        'source' => 'twilio_webhook',
    ]);
});
