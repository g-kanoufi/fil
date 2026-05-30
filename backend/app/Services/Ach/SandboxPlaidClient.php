<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\PlaidClient;
use Illuminate\Support\Str;

final class SandboxPlaidClient implements PlaidClient
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function createLinkToken(string $clientUserId, ?string $accessToken = null): array
    {
        return [
            'link_token' => 'sandbox-link-'.Str::lower(Str::random(16)),
            'expiration' => now()->addHours(4)->toIso8601String(),
        ];
    }

    public function exchangePublicToken(string $publicToken): array
    {
        return [
            'access_token' => 'sandbox-access-'.Str::lower(Str::random(12)),
            'item_id' => 'sandbox-item-'.Str::lower(Str::random(8)),
        ];
    }

    public function createDwollaProcessorToken(string $accessToken, string $accountId): string
    {
        return 'sandbox-processor-'.Str::lower(Str::random(12));
    }
}
