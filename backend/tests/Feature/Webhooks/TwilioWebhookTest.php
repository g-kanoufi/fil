<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\TwilioSignatureVerifier;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwilioWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_inbound_sms_creates_communication(): void
    {
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

        $this->assertSame(1, Communication::query()->count());
    }

    public function test_inbound_sms_links_matching_lead(): void
    {
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
    }

    public function test_inbound_sms_rejects_invalid_signature_when_token_configured(): void
    {
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
    }

    public function test_inbound_sms_accepts_valid_signature_when_token_configured(): void
    {
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
    }
}
