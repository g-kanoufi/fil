<?php

declare(strict_types=1);

namespace App\Contracts\Ach;

interface DwollaClient
{
    public function isConfigured(): bool;

    public function environment(): string;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function createClientToken(array $payload): string;

    /**
     * @return array{status: string, type?: string|null}
     */
    public function getCustomer(string $customerId): array;

    public function certifyBeneficialOwnership(string $customerId): bool;

    /**
     * @param  array{source: string, destination: string, amount: float, correlation_id?: string}  $payload
     * @return array{id: string, status: string}
     */
    public function createTransfer(array $payload): array;

    /**
     * @return array{id: string, status: string}
     */
    public function createFundingSource(string $customerId, string $processorToken, ?string $name = null): array;
}
