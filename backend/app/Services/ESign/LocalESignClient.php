<?php

declare(strict_types=1);

namespace App\Services\ESign;

use App\Contracts\ESign\ESignClient;
use App\Models\FddDelivery;
use App\Models\Signature;
use App\Models\User;

final class LocalESignClient implements ESignClient
{
    public function driver(): string
    {
        return 'local';
    }

    public function beginSigning(FddDelivery $delivery, User $signer, Signature $pendingSignature): array
    {
        return ['mode' => 'local'];
    }

    public function completeFromWebhook(array $payload): ?Signature
    {
        return null;
    }
}
