<?php

declare(strict_types=1);

namespace App\Services\ESign;

use App\Contracts\ESign\ESignClient;
use App\Models\FddDelivery;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Simulates a third-party e-sign vendor for staging and local dev.
 */
final class SandboxESignClient implements ESignClient
{
    public function driver(): string
    {
        return 'sandbox';
    }

    public function beginSigning(FddDelivery $delivery, User $signer, Signature $pendingSignature): array
    {
        $reference = 'sandbox_'.Str::random(24);

        $pendingSignature->update([
            'vendor' => $this->driver(),
            'vendor_reference' => $reference,
        ]);

        $appUrl = rtrim((string) config('fil-platform.portal.app_url', config('app.url')), '/');

        return [
            'mode' => 'embedded',
            'vendor_reference' => $reference,
            'signing_url' => "{$appUrl}/portal/fdd/{$delivery->id}?ref={$reference}",
        ];
    }

    public function completeFromWebhook(array $payload): ?Signature
    {
        $reference = $payload['vendor_reference'] ?? null;

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        $signature = Signature::query()
            ->where('vendor', $this->driver())
            ->where('vendor_reference', $reference)
            ->where('status', 'pending')
            ->first();

        return $signature;
    }
}
