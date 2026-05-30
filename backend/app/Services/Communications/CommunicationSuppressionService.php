<?php

declare(strict_types=1);

namespace App\Services\Communications;

use App\Models\CommunicationSuppression;

final class CommunicationSuppressionService
{
    public function normalizeEmail(string $email): string
    {
        return strtolower(trim($email));
    }

    public function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (strlen($digits) === 11 && str_starts_with($digits, '1')) {
            return substr($digits, 1);
        }

        return $digits;
    }

    public function isSuppressed(string $channel, string $address): bool
    {
        $normalized = $this->normalize($channel, $address);

        if ($normalized === '') {
            return false;
        }

        return CommunicationSuppression::query()
            ->where('channel', $channel)
            ->where('address', $normalized)
            ->exists();
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function suppress(
        string $channel,
        string $address,
        string $reason,
        ?string $source = null,
        array $meta = [],
    ): CommunicationSuppression {
        $normalized = $this->normalize($channel, $address);

        return CommunicationSuppression::query()->updateOrCreate(
            [
                'channel' => $channel,
                'address' => $normalized,
            ],
            [
                'reason' => $reason,
                'source' => $source,
                'meta' => $meta !== [] ? $meta : null,
            ],
        );
    }

    private function normalize(string $channel, string $address): string
    {
        return $channel === 'sms'
            ? $this->normalizePhone($address)
            : $this->normalizeEmail($address);
    }
}
