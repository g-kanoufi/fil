<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\DwollaClient;
use App\Contracts\Ach\PlaidClient;
use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\Store;
use Illuminate\Support\Str;
use RuntimeException;

final class AchPlaidLinkService
{
    public function __construct(
        private readonly PlaidClient $plaid,
        private readonly DwollaClient $dwolla,
    ) {}

    /**
     * @return array{mode: string, link_token: string, expiration: string}
     */
    public function createLinkToken(Store $store): array
    {
        $customer = $this->customerForStore($store);
        $accessToken = is_array($customer?->profile)
            ? (string) ($customer->profile['plaid_access_token'] ?? '')
            : '';

        $token = $this->plaid->createLinkToken(
            'store-'.$store->id,
            $accessToken !== '' ? $accessToken : null,
        );

        return [
            'mode' => $this->plaid->isConfigured() ? 'live' : 'sandbox',
            ...$token,
        ];
    }

    /**
     * @param  array{
     *   sandbox?: bool,
     *   public_token?: string,
     *   account_id?: string,
     *   account_name?: string|null,
     *   verification_status?: string|null
     * }  $payload
     * @return array{
     *   status: string,
     *   funding_source?: array{id: int, external_funding_source_id: string, name: string|null, status: string},
     *   pending_account?: array{id: string, name: string|null, verification_status: string|null}
     * }
     */
    public function completeLink(Store $store, array $payload): array
    {
        $customer = $this->requireCustomer($store);

        if (($payload['sandbox'] ?? false) === true && ! $this->plaid->isConfigured()) {
            return $this->completeSandboxLink($customer, $payload);
        }

        $publicToken = (string) ($payload['public_token'] ?? '');

        if ($publicToken === '') {
            throw new RuntimeException('public_token is required.');
        }

        $exchange = $this->plaid->exchangePublicToken($publicToken);
        $profile = is_array($customer->profile) ? $customer->profile : [];
        $profile['plaid_access_token'] = $exchange['access_token'];
        $profile['plaid_item_id'] = $exchange['item_id'];
        $customer->profile = $profile;
        $customer->save();

        $accountId = (string) ($payload['account_id'] ?? '');
        $accountName = isset($payload['account_name']) ? (string) $payload['account_name'] : null;
        $verificationStatus = isset($payload['verification_status']) ? (string) $payload['verification_status'] : null;

        if ($verificationStatus === 'pending_manual_verification') {
            return $this->savePendingAccount($customer, $accountId, $accountName, $verificationStatus);
        }

        if ($accountId === '') {
            throw new RuntimeException('account_id is required.');
        }

        $processorToken = $this->plaid->createDwollaProcessorToken($exchange['access_token'], $accountId);
        $fundingSource = $this->createFundingSourceRecord(
            $customer,
            $processorToken,
            $accountId,
            $accountName,
        );

        $this->removePendingAccount($customer, $accountId);

        return [
            'status' => 'linked',
            'funding_source' => $fundingSource,
        ];
    }

    /**
     * @param  array{account_name?: string|null}  $payload
     * @return array{
     *   status: string,
     *   funding_source: array{id: int, external_funding_source_id: string, name: string|null, status: string}
     * }
     */
    private function completeSandboxLink(AchCustomer $customer, array $payload): array
    {
        $accountName = isset($payload['account_name']) && $payload['account_name'] !== ''
            ? (string) $payload['account_name']
            : 'Sandbox checking';

        $externalId = 'sandbox-fs-'.Str::lower(Str::random(12));

        $source = AchFundingSource::query()->create([
            'ach_customer_id' => $customer->id,
            'external_funding_source_id' => $externalId,
            'name' => $accountName,
            'type' => 'bank',
            'status' => 'active',
            'is_default' => ! $customer->fundingSources()->exists(),
            'meta' => [
                'sandbox' => true,
            ],
        ]);

        return [
            'status' => 'linked',
            'funding_source' => $this->serializeFundingSource($source),
        ];
    }

    /**
     * @return array{
     *   status: string,
     *   pending_account: array{id: string, name: string|null, verification_status: string|null}
     * }
     */
    private function savePendingAccount(
        AchCustomer $customer,
        string $accountId,
        ?string $accountName,
        ?string $verificationStatus,
    ): array {
        $profile = is_array($customer->profile) ? $customer->profile : [];
        $pending = is_array($profile['plaid_pending_verification_accounts'] ?? null)
            ? $profile['plaid_pending_verification_accounts']
            : [];

        $pending[] = [
            'id' => $accountId,
            'name' => $accountName,
            'verification_status' => $verificationStatus,
            'created_at' => now()->toIso8601String(),
        ];

        $profile['plaid_pending_verification_accounts'] = $pending;
        $customer->profile = $profile;
        $customer->save();

        return [
            'status' => 'pending_verification',
            'pending_account' => [
                'id' => $accountId,
                'name' => $accountName,
                'verification_status' => $verificationStatus,
            ],
        ];
    }

    /**
     * @return array{id: int, external_funding_source_id: string, name: string|null, status: string}
     */
    private function createFundingSourceRecord(
        AchCustomer $customer,
        string $processorToken,
        string $accountId,
        ?string $accountName,
    ): array {
        $result = $this->dwolla->createFundingSource(
            $customer->external_customer_id,
            $processorToken,
            $accountName,
        );

        $source = AchFundingSource::query()->create([
            'ach_customer_id' => $customer->id,
            'external_funding_source_id' => $result['id'],
            'name' => $accountName,
            'type' => 'bank',
            'status' => $result['status'],
            'is_default' => ! $customer->fundingSources()->exists(),
            'meta' => [
                'plaid_account_id' => $accountId,
            ],
        ]);

        return $this->serializeFundingSource($source);
    }

    private function removePendingAccount(AchCustomer $customer, string $accountId): void
    {
        if ($accountId === '') {
            return;
        }

        $profile = is_array($customer->profile) ? $customer->profile : [];
        $pending = is_array($profile['plaid_pending_verification_accounts'] ?? null)
            ? $profile['plaid_pending_verification_accounts']
            : [];

        $profile['plaid_pending_verification_accounts'] = array_values(array_filter(
            $pending,
            static fn (array $account): bool => (string) ($account['id'] ?? '') !== $accountId,
        ));

        $customer->profile = $profile;
        $customer->save();
    }

    /**
     * @return array{id: int, external_funding_source_id: string, name: string|null, status: string}
     */
    private function serializeFundingSource(AchFundingSource $source): array
    {
        return [
            'id' => $source->id,
            'external_funding_source_id' => $source->external_funding_source_id,
            'name' => $source->name,
            'status' => $source->status,
        ];
    }

    private function customerForStore(Store $store): ?AchCustomer
    {
        return AchCustomer::query()
            ->where('owner_type', Store::class)
            ->where('owner_id', $store->id)
            ->first();
    }

    private function requireCustomer(Store $store): AchCustomer
    {
        $customer = $this->customerForStore($store);

        if ($customer === null) {
            throw new RuntimeException('Store is not enrolled for ACH.');
        }

        return $customer;
    }
}
