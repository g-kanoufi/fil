<?php

namespace App\Providers;

use App\Contracts\Ach\DwollaClient;
use App\Contracts\Ach\PlaidClient;
use App\Services\Ach\HttpDwollaClient;
use App\Services\Ach\HttpPlaidClient;
use App\Services\Ach\SandboxDwollaClient;
use App\Services\Ach\SandboxPlaidClient;
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DwollaClient::class, function (): DwollaClient {
            $client = app(HttpDwollaClient::class);

            if ($client->isConfigured() || app()->environment('local', 'testing')) {
                return $client->isConfigured() ? $client : app(SandboxDwollaClient::class);
            }

            return $client;
        });

        $this->app->bind(PlaidClient::class, function (): PlaidClient {
            $client = app(HttpPlaidClient::class);

            if ($client->isConfigured() || app()->environment('local', 'testing')) {
                return $client->isConfigured() ? $client : app(SandboxPlaidClient::class);
            }

            return $client;
        });
    }

    public function boot(): void
    {
        app(OutboundMailGuard::class)->enforceSafeMailer();
        $this->enforceFinancialIntegrationConfig();
    }

    private function enforceFinancialIntegrationConfig(): void
    {
        if (! app()->environment('production', 'staging')) {
            return;
        }

        if (! (bool) config('fil-royalties.enable_ach_royalty_collection')) {
            return;
        }

        $dwolla = app(HttpDwollaClient::class);
        $plaid = app(HttpPlaidClient::class);

        if (! $dwolla->isConfigured() || ! $plaid->isConfigured()) {
            throw new RuntimeException(
                'FIL_ENABLE_ACH_COLLECTION is enabled but Dwolla and Plaid credentials are not configured.',
            );
        }
    }
}
