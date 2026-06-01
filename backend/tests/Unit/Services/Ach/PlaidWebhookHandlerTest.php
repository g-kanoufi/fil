<?php

declare(strict_types=1);

use App\Models\AchCustomer;
use App\Models\Store;
use App\Services\Ach\PlaidWebhookHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('plaid item error webhook updates customer profile status', function () {
    $store = Store::factory()->create();

    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-plaid',
        'status' => 'active',
        'profile' => [
            'plaid_item_id' => 'item_test_123',
            'plaid_access_token' => 'access-secret',
        ],
    ]);

    app(PlaidWebhookHandler::class)->handle([
        'webhook_type' => 'ITEM',
        'webhook_code' => 'ERROR',
        'item_id' => 'item_test_123',
        'error' => ['error_code' => 'ITEM_LOGIN_REQUIRED'],
    ]);

    $customer->refresh();

    expect($customer->profile['plaid_item_status'] ?? null)->toBe('error');
    expect($customer->profile['plaid_error'] ?? null)->toBe(['error_code' => 'ITEM_LOGIN_REQUIRED']);
});

test('plaid auth automatically verified webhook updates pending account', function () {
    $store = Store::factory()->create();

    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-auth',
        'status' => 'active',
        'profile' => [
            'plaid_item_id' => 'item_auth_456',
            'plaid_pending_verification_accounts' => [
                [
                    'id' => 'acc_pending_1',
                    'name' => 'Checking',
                    'verification_status' => 'pending_manual_verification',
                ],
            ],
        ],
    ]);

    app(PlaidWebhookHandler::class)->handle([
        'webhook_type' => 'AUTH',
        'webhook_code' => 'AUTOMATICALLY_VERIFIED',
        'item_id' => 'item_auth_456',
        'account_id' => 'acc_pending_1',
    ]);

    $customer->refresh();
    $pending = $customer->profile['plaid_pending_verification_accounts'][0] ?? [];

    expect($pending['verification_status'] ?? null)->toBe('verified');
});

test('plaid user permission revoked clears token and deactivates customer', function () {
    $store = Store::factory()->create();

    $customer = AchCustomer::query()->create([
        'owner_type' => Store::class,
        'owner_id' => $store->id,
        'provider' => 'dwolla',
        'external_customer_id' => 'cust-revoked',
        'status' => 'active',
        'profile' => [
            'plaid_item_id' => 'item_revoked',
            'plaid_access_token' => 'access-secret',
        ],
    ]);

    app(PlaidWebhookHandler::class)->handle([
        'webhook_type' => 'ITEM',
        'webhook_code' => 'USER_PERMISSION_REVOKED',
        'item_id' => 'item_revoked',
    ]);

    $customer->refresh();

    expect($customer->status)->toBe('inactive');
    expect($customer->profile)->not->toHaveKey('plaid_access_token');
    expect($customer->profile['plaid_item_status'] ?? null)->toBe('revoked');
});
