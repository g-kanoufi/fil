<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\DwollaClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

final class HttpDwollaClient implements DwollaClient
{
    public function __construct(
        private readonly DwollaAuthService $auth,
    ) {}

    public function isConfigured(): bool
    {
        return $this->auth->isConfigured();
    }

    public function environment(): string
    {
        return $this->auth->environment();
    }

    public function createClientToken(array $payload): string
    {
        if (! $this->isConfigured()) {
            return $this->sandboxOrThrow()->createClientToken($payload);
        }

        $response = Http::withToken($this->auth->accessToken())
            ->acceptJson()
            ->post($this->auth->baseUrl().'/client-tokens', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Dwolla client token request failed.');
        }

        return (string) ($response->json('token') ?? '');
    }

    public function getCustomer(string $customerId): array
    {
        if (! $this->isConfigured()) {
            return $this->sandboxOrThrow()->getCustomer($customerId);
        }

        $response = Http::withToken($this->auth->accessToken())
            ->acceptJson()
            ->get($this->auth->baseUrl().'/customers/'.$customerId);

        if (! $response->successful()) {
            throw new RuntimeException('Dwolla customer lookup failed.');
        }

        return [
            'status' => (string) ($response->json('status') ?? 'unknown'),
            'type' => $response->json('type'),
        ];
    }

    public function certifyBeneficialOwnership(string $customerId): bool
    {
        if (! $this->isConfigured()) {
            return $this->sandboxOrThrow()->certifyBeneficialOwnership($customerId);
        }

        $response = Http::withToken($this->auth->accessToken())
            ->acceptJson()
            ->post($this->auth->baseUrl().'/customers/'.$customerId.'/beneficial-ownership', [
                'status' => 'certified',
            ]);

        return $response->successful();
    }

    public function createTransfer(array $payload): array
    {
        if (! $this->isConfigured()) {
            return $this->sandboxOrThrow()->createTransfer($payload);
        }

        $base = $this->auth->baseUrl();

        $response = Http::withToken($this->auth->accessToken())
            ->acceptJson()
            ->post($base.'/transfers', [
                '_links' => [
                    'source' => ['href' => $base.'/funding-sources/'.$payload['source']],
                    'destination' => ['href' => $base.'/funding-sources/'.$payload['destination']],
                ],
                'amount' => [
                    'currency' => 'USD',
                    'value' => number_format((float) $payload['amount'], 2, '.', ''),
                ],
                'correlationId' => $payload['correlation_id'] ?? Str::uuid()->toString(),
            ]);

        if (! $response->successful()) {
            return [
                'id' => null,
                'status' => 'failed',
            ];
        }

        $location = $response->header('Location') ?? '';
        $id = $location !== '' ? basename($location) : null;

        return [
            'id' => $id ?? 'unknown',
            'status' => 'pending',
        ];
    }

    public function createFundingSource(string $customerId, string $processorToken, ?string $name = null): array
    {
        if (! $this->isConfigured()) {
            return $this->sandboxOrThrow()->createFundingSource($customerId, $processorToken, $name);
        }

        $base = $this->auth->baseUrl();
        $body = [
            'plaidToken' => $processorToken,
        ];

        if ($name !== null && $name !== '') {
            $body['name'] = $name;
        }

        $response = Http::withToken($this->auth->accessToken())
            ->acceptJson()
            ->post($base.'/customers/'.$customerId.'/funding-sources', $body);

        if (! $response->successful()) {
            return [
                'id' => 'failed',
                'status' => 'failed',
            ];
        }

        $location = $response->header('Location') ?? '';
        $id = $location !== '' ? basename($location) : 'unknown';

        return [
            'id' => $id,
            'status' => 'active',
        ];
    }

    private function sandboxOrThrow(): DwollaClient
    {
        if (app()->environment('local', 'testing')) {
            return app(SandboxDwollaClient::class);
        }

        throw new RuntimeException('Dwolla is not configured.');
    }
}
