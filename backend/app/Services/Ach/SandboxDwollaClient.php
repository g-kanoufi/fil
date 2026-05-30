<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Contracts\Ach\DwollaClient;
use Illuminate\Support\Str;

final class SandboxDwollaClient implements DwollaClient
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function environment(): string
    {
        return 'sandbox';
    }

    public function createClientToken(array $payload): string
    {
        return 'sandbox-dwolla-client-'.Str::lower(Str::random(16));
    }

    public function getCustomer(string $customerId): array
    {
        return [
            'status' => 'verified',
            'type' => 'business',
        ];
    }

    public function certifyBeneficialOwnership(string $customerId): bool
    {
        return true;
    }

    public function createTransfer(array $payload): array
    {
        return [
            'id' => 'sandbox-transfer-'.Str::lower(Str::random(12)),
            'status' => 'sandbox_queued',
        ];
    }

    public function createFundingSource(string $customerId, string $processorToken, ?string $name = null): array
    {
        return [
            'id' => 'sandbox-fs-'.Str::lower(Str::random(12)),
            'status' => 'active',
        ];
    }
}
