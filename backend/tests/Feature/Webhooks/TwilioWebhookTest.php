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
