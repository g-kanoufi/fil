<?php

declare(strict_types=1);
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('plaid webhook rejects missing verification in production', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config([
        'services.plaid.client_id' => 'plaid-client-id',
        'services.plaid.secret' => 'plaid-secret',
    ]);

    $this->postJson('/api/webhooks/plaid', [
        'webhook_type' => 'ITEM',
        'webhook_code' => 'PENDING_EXPIRATION',
    ])->assertForbidden();
});
test('plaid webhook accepts when plaid not configured in testing', function () {
    config([
        'services.plaid.client_id' => '',
        'services.plaid.secret' => '',
    ]);

    $this->postJson('/api/webhooks/plaid', [
        'webhook_type' => 'ITEM',
        'webhook_code' => 'PENDING_EXPIRATION',
    ])->assertOk()
        ->assertJsonPath('received', true);
});
