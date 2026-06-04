<?php

declare(strict_types=1);

namespace App\Services\ESign;

use App\Contracts\ESign\ESignClient;
use App\Models\FddDelivery;
use App\Models\Signature;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Dropbox Sign (HelloSign) embedded signing for FDD Item 23 receipt.
 */
final class DropboxSignESignClient implements ESignClient
{
    public function driver(): string
    {
        return 'dropbox_sign';
    }

    public function beginSigning(FddDelivery $delivery, User $signer, Signature $pendingSignature): array
    {
        $apiKey = (string) config('fil-platform.esign.dropbox_sign.api_key');

        if ($apiKey === '') {
            throw new RuntimeException('Dropbox Sign API key is not configured.');
        }

        $title = $delivery->fdd?->title ?? 'Franchise Disclosure Document';
        $signerName = $signer->name ?: trim(($signer->first_name ?? '').' '.($signer->last_name ?? ''));
        $signerEmail = (string) $signer->email;

        $response = Http::withBasicAuth($apiKey, '')
            ->asMultipart()
            ->post('https://api.hellosign.com/v3/signature_request/create_embedded', [
                ['name' => 'title', 'contents' => "FDD Item 23 — {$title}"],
                ['name' => 'subject', 'contents' => 'Please sign your FDD receipt'],
                ['name' => 'message', 'contents' => 'Sign to acknowledge receipt of the Franchise Disclosure Document.'],
                ['name' => 'signers[0][email_address]', 'contents' => $signerEmail],
                ['name' => 'signers[0][name]', 'contents' => $signerName !== '' ? $signerName : $signerEmail],
                ['name' => 'client_id', 'contents' => (string) config('fil-platform.esign.dropbox_sign.client_id')],
                ['name' => 'test_mode', 'contents' => app()->environment('production') ? '0' : '1'],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Dropbox Sign request failed.');
        }

        $signatureId = data_get($response->json(), 'signature_request.signatures.0.signature_id');
        $requestId = data_get($response->json(), 'signature_request.signature_request_id');

        if (! is_string($signatureId) || ! is_string($requestId)) {
            throw new RuntimeException('Dropbox Sign response missing signature identifiers.');
        }

        $embedded = Http::withBasicAuth($apiKey, '')
            ->get("https://api.hellosign.com/v3/embedded/sign_url/{$signatureId}");

        if (! $embedded->successful()) {
            throw new RuntimeException('Dropbox Sign embedded URL request failed.');
        }

        $signUrl = $embedded->json('embedded.sign_url');

        if (! is_string($signUrl) || $signUrl === '') {
            throw new RuntimeException('Dropbox Sign did not return an embedded URL.');
        }

        $pendingSignature->update([
            'vendor' => $this->driver(),
            'vendor_reference' => $requestId,
            'extras' => array_merge($pendingSignature->extras ?? [], [
                'dropbox_signature_id' => $signatureId,
            ]),
        ]);

        return [
            'mode' => 'embedded',
            'vendor_reference' => $requestId,
            'signing_url' => $signUrl,
        ];
    }

    public function completeFromWebhook(array $payload): ?Signature
    {
        $reference = data_get($payload, 'signature_request.signature_request_id');

        if (! is_string($reference) || $reference === '') {
            return null;
        }

        return Signature::query()
            ->where('vendor', $this->driver())
            ->where('vendor_reference', $reference)
            ->where('status', 'pending')
            ->first();
    }
}
