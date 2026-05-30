<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class AchDwollaEnrollmentSession
{
    private const TTL_MINUTES = 30;

    public function markCreateIntent(Store $store, User $user): void
    {
        $this->put($store, $user, [
            'mode' => 'create',
        ]);
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    public function markIntentFromClientToken(Store $store, User $user, string $action, ?array $links): void
    {
        if ($action === 'customer.create') {
            $this->markCreateIntent($store, $user);

            return;
        }

        $customerId = $this->extractCustomerId($links);

        if ($customerId === null) {
            throw new RuntimeException('Dwolla client token request must include a customer link.');
        }

        $this->put($store, $user, [
            'mode' => 'update',
            'customer_id' => $customerId,
        ]);
    }

    public function assertCanEnroll(Store $store, User $user, string $externalCustomerId): void
    {
        /** @var array{mode?: string, customer_id?: string}|null $pending */
        $pending = Cache::get($this->cacheKey($store, $user));

        if (! is_array($pending)) {
            throw new RuntimeException('Request a Dwolla client token before enrolling.');
        }

        if (($pending['mode'] ?? '') === 'update') {
            $expected = (string) ($pending['customer_id'] ?? '');

            if ($expected === '' || ! hash_equals($expected, $externalCustomerId)) {
                throw new RuntimeException('Customer ID does not match the verified Dwolla session.');
            }
        }
    }

    public function clear(Store $store, User $user): void
    {
        Cache::forget($this->cacheKey($store, $user));
    }

    /**
     * @param  array<string, mixed>|null  $links
     */
    private function extractCustomerId(?array $links): ?string
    {
        if ($links === null) {
            return null;
        }

        $href = data_get($links, 'customer.href');

        if (! is_string($href) || $href === '') {
            return null;
        }

        if (preg_match('#/customers/([^/?#]+)#', $href, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function put(Store $store, User $user, array $payload): void
    {
        Cache::put(
            $this->cacheKey($store, $user),
            $payload,
            now()->addMinutes(self::TTL_MINUTES),
        );
    }

    private function cacheKey(Store $store, User $user): string
    {
        return sprintf('ach:dwolla:pending:%d:%d', $store->id, $user->id);
    }
}
