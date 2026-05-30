<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\DwollaClient;
use App\Models\AchCustomer;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Str;
use RuntimeException;

final class AchDwollaEnrollmentService
{
    public function __construct(
        private readonly DwollaClient $dwolla,
        private readonly AchDwollaEnrollmentSession $enrollmentSession,
    ) {}

    /**
     * @return array{
     *   dwolla_mode: string,
     *   dwolla_environment: string,
     *   terms_url: string,
     *   privacy_url: string,
     *   ownership_certified: bool
     * }
     */
    public function config(): array
    {
        return [
            'dwolla_mode' => $this->dwolla->isConfigured() ? 'live' : 'sandbox',
            'dwolla_environment' => $this->dwolla->environment(),
            'terms_url' => (string) config('services.dwolla.terms_url'),
            'privacy_url' => (string) config('services.dwolla.privacy_url'),
            'ownership_certified' => false,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function enrollmentPayload(?AchCustomer $customer): array
    {
        $config = $this->config();

        if ($customer === null) {
            return $config;
        }

        $profile = is_array($customer->profile) ? $customer->profile : [];

        if ($this->dwolla->isConfigured() && ! (($profile['sandbox'] ?? false) === true)) {
            $remote = $this->dwolla->getCustomer($customer->external_customer_id);
            $customer->status = (string) ($remote['status'] ?? $customer->status);
            $customer->save();
        }

        return [
            ...$config,
            'ownership_certified' => (bool) ($profile['ownership_certified'] ?? false),
        ];
    }

    public function enroll(Store $store, User $user, string $externalCustomerId): AchCustomer
    {
        $externalCustomerId = trim($externalCustomerId);

        if ($externalCustomerId === '') {
            throw new RuntimeException('external_customer_id is required.');
        }

        $this->enrollmentSession->assertCanEnroll($store, $user, $externalCustomerId);

        $remote = $this->dwolla->getCustomer($externalCustomerId);

        $customer = AchCustomer::query()->updateOrCreate(
            [
                'owner_type' => Store::class,
                'owner_id' => $store->id,
                'provider' => 'dwolla',
            ],
            [
                'external_customer_id' => $externalCustomerId,
                'status' => (string) ($remote['status'] ?? 'unverified'),
                'profile' => [
                    'customer_type' => $remote['type'] ?? null,
                    'enrolled_at' => now()->toIso8601String(),
                ],
            ],
        );

        $this->enrollmentSession->clear($store, $user);

        return $customer;
    }

    public function sandboxEnroll(Store $store): AchCustomer
    {
        if ($this->dwolla->isConfigured()) {
            throw new RuntimeException('Sandbox enrollment is unavailable when Dwolla credentials are configured.');
        }

        return AchCustomer::query()->updateOrCreate(
            [
                'owner_type' => Store::class,
                'owner_id' => $store->id,
                'provider' => 'dwolla',
            ],
            [
                'external_customer_id' => 'sandbox-cust-'.Str::lower(Str::random(12)),
                'status' => 'verified',
                'profile' => [
                    'sandbox' => true,
                    'ownership_certified' => true,
                    'enrolled_at' => now()->toIso8601String(),
                ],
            ],
        );
    }

    public function certifyOwnership(Store $store): AchCustomer
    {
        $customer = $this->requireCustomer($store);

        if (! $this->dwolla->certifyBeneficialOwnership($customer->external_customer_id)) {
            throw new RuntimeException('Beneficial ownership certification failed.');
        }

        $profile = is_array($customer->profile) ? $customer->profile : [];
        $profile['ownership_certified'] = true;
        $customer->profile = $profile;
        $customer->save();

        return $customer;
    }

    private function requireCustomer(Store $store): AchCustomer
    {
        $customer = AchCustomer::query()
            ->where('owner_type', Store::class)
            ->where('owner_id', $store->id)
            ->first();

        if ($customer === null) {
            throw new RuntimeException('Store is not enrolled for ACH.');
        }

        return $customer;
    }
}
