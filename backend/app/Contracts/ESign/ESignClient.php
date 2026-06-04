<?php

declare(strict_types=1);

namespace App\Contracts\ESign;

use App\Models\FddDelivery;
use App\Models\Signature;
use App\Models\User;

interface ESignClient
{
    public function driver(): string;

    /**
     * @return array{mode: 'local'|'embedded', signing_url?: string, vendor_reference?: string}
     */
    public function beginSigning(FddDelivery $delivery, User $signer, Signature $pendingSignature): array;

    public function completeFromWebhook(array $payload): ?Signature;
}
