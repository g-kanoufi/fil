<?php

declare(strict_types=1);

namespace App\Services\Ach;

use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

final class PlaidWebhookVerifier
{
    public function verify(Request $request): bool
    {
        if (! $this->isConfigured()) {
            return app()->environment('local', 'testing');
        }

        $jwt = (string) $request->header('Plaid-Verification', '');

        if ($jwt === '') {
            return false;
        }

        try {
            $header = JWT::jsonDecode(JWT::urlsafeB64Decode(explode('.', $jwt)[0] ?? ''));

            if (! is_object($header) || ($header->alg ?? null) !== 'ES256') {
                return false;
            }

            $keyId = (string) ($header->kid ?? '');

            if ($keyId === '') {
                return false;
            }

            $jwk = $this->verificationKey($keyId);

            if ($jwk === null) {
                return false;
            }

            $decoded = JWT::decode($jwt, JWK::parseKey($jwk, 'ES256'));

            $issuedAt = (int) ($decoded->iat ?? 0);

            if ($issuedAt <= 0 || $issuedAt < time() - 300) {
                return false;
            }

            $expectedHash = strtolower((string) ($decoded->request_body_sha256 ?? ''));
            $bodyHash = hash('sha256', $request->getContent());

            return $expectedHash !== '' && hash_equals($expectedHash, $bodyHash);
        } catch (Throwable) {
            return false;
        }
    }

    private function isConfigured(): bool
    {
        return (string) config('services.plaid.client_id') !== ''
            && (string) config('services.plaid.secret') !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    private function verificationKey(string $keyId): ?array
    {
        $cacheKey = 'plaid:webhook:jwk:'.$keyId;

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return $cached;
        }

        $response = Http::acceptJson()
            ->post($this->baseUrl().'/webhook_verification_key/get', [
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret'),
                'key_id' => $keyId,
            ]);

        if (! $response->successful()) {
            return null;
        }

        /** @var array{key?: array<string, mixed>} $body */
        $body = $response->json();
        $key = $body['key'] ?? null;

        if (! is_array($key)) {
            return null;
        }

        Cache::put($cacheKey, $key, now()->addHours(24));

        return $key;
    }

    private function baseUrl(): string
    {
        return match ((string) config('services.plaid.environment', 'sandbox')) {
            'production' => 'https://production.plaid.com',
            'development' => 'https://development.plaid.com',
            default => 'https://sandbox.plaid.com',
        };
    }
}
