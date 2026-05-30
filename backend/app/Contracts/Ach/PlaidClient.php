<?php

declare(strict_types=1);

namespace App\Contracts\Ach;

interface PlaidClient
{
    public function isConfigured(): bool;

    /**
     * @return array{link_token: string, expiration: string}
     */
    public function createLinkToken(string $clientUserId, ?string $accessToken = null): array;

    /**
     * @return array{access_token: string, item_id: string}
     */
    public function exchangePublicToken(string $publicToken): array;

    public function createDwollaProcessorToken(string $accessToken, string $accountId): string;
}
