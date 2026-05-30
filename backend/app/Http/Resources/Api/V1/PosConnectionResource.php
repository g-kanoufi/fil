<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\PosConnection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PosConnection */
final class PosConnectionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'provider' => $this->provider,
            'external_location_id' => $this->external_location_id,
            'status' => $this->status,
            'last_synced_at' => $this->last_synced_at?->toIso8601String(),
        ];
    }
}
