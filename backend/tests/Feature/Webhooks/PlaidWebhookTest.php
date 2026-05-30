<?php

declare(strict_types=1);

namespace Tests\Feature\Webhooks;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class PlaidWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_plaid_webhook_rejects_missing_verification_in_production(): void
    {
        app()->detectEnvironment(fn (): string => 'production');
        config([
            'services.plaid.client_id' => 'plaid-client-id',
            'services.plaid.secret' => 'plaid-secret',
        ]);

        $this->postJson('/api/webhooks/plaid', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'PENDING_EXPIRATION',
        ])->assertForbidden();
    }

    public function test_plaid_webhook_accepts_when_plaid_not_configured_in_testing(): void
    {
        config([
            'services.plaid.client_id' => '',
            'services.plaid.secret' => '',
        ]);

        $this->postJson('/api/webhooks/plaid', [
            'webhook_type' => 'ITEM',
            'webhook_code' => 'PENDING_EXPIRATION',
        ])->assertOk()
            ->assertJsonPath('received', true);
    }
}
