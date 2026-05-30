<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class MailgunInboundWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_inbound_email_matches_lead_by_sender_email(): void
    {
        config(['services.mailgun.webhook_signing_key' => 'signing-key']);

        $prospect = User::factory()->create(['email' => 'reply@prospect.com', 'name' => 'Reply Prospect']);
        $lead = Lead::factory()->create(['prospect_user_id' => $prospect->id]);

        $timestamp = (string) time();
        $token = 'token-value';
        $signature = hash_hmac('sha256', $timestamp.$token, 'signing-key');

        $this->post('/api/webhooks/mailgun/inbound', [
            'timestamp' => $timestamp,
            'token' => $token,
            'signature' => $signature,
            'sender' => 'Reply Prospect <reply@prospect.com>',
            'recipient' => 'staff@client.com',
            'subject' => 'Re: Your FDD',
            'stripped-text' => 'Thanks, I have a question.',
            'Message-Id' => '<inbound-msg-1@mailgun>',
        ])->assertOk();

        $this->assertDatabaseHas('communications', [
            'lead_id' => $lead->id,
            'recipient_user_id' => $prospect->id,
            'direction' => 'inbound',
            'type' => 'email',
            'message' => 'Thanks, I have a question.',
            'external_message_id' => 'inbound-msg-1@mailgun',
        ]);

        $this->assertSame(1, Communication::query()->count());
    }
}
