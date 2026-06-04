<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Contracts\Pos\PosRevenueClient;
use App\Models\PosConnection;

final class PosRevenueClientResolver
{
    public function __construct(
        private readonly SquarePosRevenueClient $square,
        private readonly SandboxPosRevenueClient $sandbox,
    ) {}

    public function forConnection(PosConnection $connection): PosRevenueClient
    {
        if ($this->square->supports($connection->provider) && $this->square->isConfiguredFor($connection)) {
            return $this->square;
        }

        return $this->sandbox;
    }
}
