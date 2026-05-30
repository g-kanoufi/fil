<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\PlaidClient;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class HttpPlaidClient implements PlaidClient
{
    public function isConfigured(): bool
    {
        return (string) config('services.plaid.client_id') !== ''
            && (string) config('services.plaid.secret') !== '';
    }

    public function createLinkToken(string $clientUserId, ?string $accessToken = null): array
    {
        $payload = [
            'client_id' => config('services.plaid.client_id'),
            'secret' => config('services.plaid.secret'),
            'client_name' => (string) config('app.name', 'FIL'),
            'language' => 'en',
            'country_codes' => ['US'],
            'user' => [
                'client_user_id' => $clientUserId,
            ],
            'link_customization_name' => 'default',
        ];

        if ($accessToken !== null && $accessToken !== '') {
            $payload['access_token'] = $accessToken;
        } else {
            $payload['products'] = ['auth'];
        }

        $webhook = (string) config('services.plaid.webhook_url');

        if ($webhook !== '') {
            $payload['webhook'] = $webhook;
        }

        $response = Http::acceptJson()
            ->post($this->baseUrl().'/link/token/create', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Plaid link token request failed.');
        }

        /** @var array{link_token?: string, expiration?: string} $body */
        $body = $response->json();

        return [
            'link_token' => (string) ($body['link_token'] ?? ''),
            'expiration' => (string) ($body['expiration'] ?? ''),
        ];
    }

    public function exchangePublicToken(string $publicToken): array
    {
        $response = Http::acceptJson()
            ->post($this->baseUrl().'/item/public_token/exchange', [
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret'),
                'public_token' => $publicToken,
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Plaid public token exchange failed.');
        }

        /** @var array{access_token?: string, item_id?: string} $body */
        $body = $response->json();

        return [
            'access_token' => (string) ($body['access_token'] ?? ''),
            'item_id' => (string) ($body['item_id'] ?? ''),
        ];
    }

    public function createDwollaProcessorToken(string $accessToken, string $accountId): string
    {
        $response = Http::acceptJson()
            ->post($this->baseUrl().'/processor/token/create', [
                'client_id' => config('services.plaid.client_id'),
                'secret' => config('services.plaid.secret'),
                'access_token' => $accessToken,
                'account_id' => $accountId,
                'processor' => 'dwolla',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Plaid processor token request failed.');
        }

        /** @var array{processor_token?: string} $body */
        $body = $response->json();

        return (string) ($body['processor_token'] ?? '');
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
