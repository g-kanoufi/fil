<?php

declare(strict_types=1);

namespace App\Actions\Royalties;

use App\Contracts\Ach\DwollaClient;
use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\AchTransfer;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class TriggerAchTransfer
{
    /**
     * @var list<string>
     */
    private const PAID_PROVIDER_STATUSES = [
        'processed',
        'pending',
        'sandbox_queued',
    ];

    public function __construct(
        private readonly DwollaClient $dwolla,
    ) {}

    public function handle(Store $store, RoyaltyPeriod $period, float $amount): AchTransfer
    {
        abort_unless($period->store_id === $store->id, 404);

        $existing = AchTransfer::query()
            ->where('store_id', $store->id)
            ->where('royalty_period_id', $period->id)
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        return DB::transaction(function () use ($store, $period, $amount): AchTransfer {
            $correlationId = Str::uuid()->toString();
            $provider = config('services.dwolla.token') ? 'dwolla' : 'sandbox';
            $externalId = null;
            $providerStatus = $provider === 'sandbox' ? 'sandbox_queued' : 'pending';

            $source = $this->defaultFundingSource($store);
            $destination = config('services.dwolla.destination_funding_source_id');

            if ($source !== null && filled($destination)) {
                $result = $this->dwolla->createTransfer([
                    'source' => $source,
                    'destination' => (string) $destination,
                    'amount' => $amount,
                    'correlation_id' => $correlationId,
                ]);

                $externalId = $result['id'] ?? null;
                $providerStatus = $result['status'] ?? $providerStatus;
            }

            return AchTransfer::query()->create([
                'store_id' => $store->id,
                'royalty_period_id' => $period->id,
                'transferred_at' => now(),
                'amount' => $amount,
                'provider' => $provider,
                'provider_status' => $providerStatus,
                'status' => self::isPaidProviderStatus($providerStatus) ? 1 : 0,
                'external_transfer_id' => $externalId,
                'correlation_id' => $correlationId,
                'royalty_name' => 'Royalty '.$period->period_start?->toDateString(),
                'description' => 'Royalty ACH for period ending '.$period->period_end?->toDateString(),
                'meta' => [
                    'royalty_period_id' => $period->id,
                    'period_start' => $period->period_start?->toDateString(),
                    'period_end' => $period->period_end?->toDateString(),
                ],
            ]);
        });
    }

    public static function isPaidProviderStatus(?string $providerStatus): bool
    {
        return in_array($providerStatus, self::PAID_PROVIDER_STATUSES, true);
    }

    private function defaultFundingSource(Store $store): ?string
    {
        $customerId = AchCustomer::query()
            ->where('owner_type', Store::class)
            ->where('owner_id', $store->id)
            ->value('id');

        if ($customerId === null) {
            return null;
        }

        return AchFundingSource::query()
            ->where('ach_customer_id', $customerId)
            ->where('is_default', true)
            ->value('external_funding_source_id');
    }
}
