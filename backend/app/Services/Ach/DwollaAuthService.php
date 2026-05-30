<?php

declare(strict_types=1);

namespace App\Services\Ach;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class DwollaAuthService
{
    public function isConfigured(): bool
    {
        return $this->staticToken() !== '' || ($this->key() !== '' && $this->secret() !== '');
    }

    public function environment(): string
    {
        return config('services.dwolla.environment') === 'production' ? 'production' : 'sandbox';
    }

    public function baseUrl(): string
    {
        return $this->environment() === 'production'
            ? 'https://api.dwolla.com'
            : 'https://api-sandbox.dwolla.com';
    }

    public function accessToken(): string
    {
        $static = $this->staticToken();

        if ($static !== '') {
            return $static;
        }

        $key = $this->key();
        $secret = $this->secret();

        if ($key === '' || $secret === '') {
            throw new RuntimeException('Dwolla credentials are not configured.');
        }

        return Cache::remember('dwolla:access_token', now()->addMinutes(45), function () use ($key, $secret): string {
            $response = Http::asForm()
                ->withBasicAuth($key, $secret)
                ->post($this->baseUrl().'/token', [
                    'grant_type' => 'client_credentials',
                ]);

            if (! $response->successful()) {
                throw new RuntimeException('Dwolla OAuth token request failed.');
            }

            $token = (string) ($response->json('access_token') ?? '');

            if ($token === '') {
                throw new RuntimeException('Dwolla OAuth token missing from response.');
            }

            return $token;
        });
    }

    private function staticToken(): string
    {
        return (string) config('services.dwolla.token');
    }

    private function key(): string
    {
        return (string) config('services.dwolla.key');
    }

    private function secret(): string
    {
        return (string) config('services.dwolla.secret');
    }
}
