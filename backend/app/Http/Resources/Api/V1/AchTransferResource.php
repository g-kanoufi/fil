<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AchTransfer;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AchTransfer */
final class AchTransferResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'store_name' => $this->whenLoaded('store', fn () => $this->store?->name),
            'area_id' => $this->area_id,
            'transferred_at' => $this->transferred_at?->toIso8601String(),
            'provider' => $this->provider,
            'provider_status' => $this->provider_status,
            'status' => $this->status,
            'amount' => $this->amount,
            'royalty_name' => $this->royalty_name,
            'description' => $this->description,
            'external_transfer_id' => $this->external_transfer_id,
        ];
    }
}
