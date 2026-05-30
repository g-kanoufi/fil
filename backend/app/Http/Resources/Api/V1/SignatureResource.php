<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Signature;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Signature */
final class SignatureResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'signed_at' => $this->signed_at?->toIso8601String(),
            'signer_user_id' => $this->signer_user_id,
            'signed_name' => $this->signature_data['signed_name'] ?? null,
            'fdd_delivery_id' => $this->fdd_delivery_id,
            'lead_id' => $this->lead_id,
        ];
    }
}
