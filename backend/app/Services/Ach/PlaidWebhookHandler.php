<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Models\AchCustomer;
use Illuminate\Support\Facades\Log;

/**
 * Handles verified Plaid ITEM and AUTH webhook events (SEC-003).
 */
final class PlaidWebhookHandler
{
    /**
     * @param  array<string, mixed>  $payload
     */
    public function handle(array $payload): void
    {
        $type = (string) ($payload['webhook_type'] ?? '');
        $code = (string) ($payload['webhook_code'] ?? '');
        $itemId = (string) ($payload['item_id'] ?? '');

        if ($itemId === '') {
            return;
        }

        $customer = $this->findCustomerByItemId($itemId);

        if ($customer === null) {
            Log::info('Plaid webhook ignored — no matching ACH customer', [
                'webhook_type' => $type,
                'webhook_code' => $code,
                'item_id' => $itemId,
            ]);

            return;
        }

        match ($type) {
            'ITEM' => $this->handleItem($customer, $code, $payload),
            'AUTH' => $this->handleAuth($customer, $code, $payload),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleItem(AchCustomer $customer, string $code, array $payload): void
    {
        $profile = is_array($customer->profile) ? $customer->profile : [];

        match ($code) {
            'ERROR' => $this->applyItemStatus($customer, $profile, 'error', [
                'plaid_error' => $payload['error'] ?? null,
            ]),
            'PENDING_EXPIRATION' => $this->applyItemStatus($customer, $profile, 'pending_expiration', [
                'consent_expiration_time' => $payload['consent_expiration_time'] ?? null,
            ]),
            'USER_PERMISSION_REVOKED' => $this->applyItemStatus($customer, $profile, 'revoked', [
                'plaid_access_token' => null,
            ]),
            'LOGIN_REQUIRED' => $this->applyItemStatus($customer, $profile, 'login_required'),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function handleAuth(AchCustomer $customer, string $code, array $payload): void
    {
        $profile = is_array($customer->profile) ? $customer->profile : [];
        $accountId = (string) ($payload['account_id'] ?? '');

        match ($code) {
            'AUTOMATICALLY_VERIFIED', 'VERIFICATION_EXPIRED' => $this->updatePendingAccountStatus(
                $customer,
                $profile,
                $accountId,
                $code === 'AUTOMATICALLY_VERIFIED' ? 'verified' : 'expired',
            ),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>  $extra
     */
    private function applyItemStatus(
        AchCustomer $customer,
        array $profile,
        string $status,
        array $extra = [],
    ): void {
        $profile['plaid_item_status'] = $status;
        $profile['plaid_item_status_at'] = now()->toIso8601String();

        foreach ($extra as $key => $value) {
            if ($value === null) {
                unset($profile[$key]);
            } else {
                $profile[$key] = $value;
            }
        }

        if ($status === 'revoked') {
            $customer->status = 'inactive';
        }

        $customer->profile = $profile;
        $customer->save();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function updatePendingAccountStatus(
        AchCustomer $customer,
        array $profile,
        string $accountId,
        string $status,
    ): void {
        if ($accountId === '') {
            return;
        }

        $pending = is_array($profile['plaid_pending_verification_accounts'] ?? null)
            ? $profile['plaid_pending_verification_accounts']
            : [];

        foreach ($pending as $index => $account) {
            if (! is_array($account)) {
                continue;
            }

            if ((string) ($account['id'] ?? '') !== $accountId) {
                continue;
            }

            $pending[$index]['verification_status'] = $status;
            $pending[$index]['verified_at'] = now()->toIso8601String();
        }

        $profile['plaid_pending_verification_accounts'] = $pending;
        $profile['plaid_auth_webhook_at'] = now()->toIso8601String();
        $customer->profile = $profile;
        $customer->save();
    }

    private function findCustomerByItemId(string $itemId): ?AchCustomer
    {
        foreach (AchCustomer::query()->lazyById(100) as $customer) {
            $profile = $customer->profile;

            if (! is_array($profile)) {
                continue;
            }

            if ((string) ($profile['plaid_item_id'] ?? '') === $itemId) {
                return $customer;
            }
        }

        return null;
    }
}
