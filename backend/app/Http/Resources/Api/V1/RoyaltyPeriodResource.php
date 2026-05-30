<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\RoyaltyPeriod;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoyaltyPeriod */
final class RoyaltyPeriodResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'frequency' => $this->frequency,
            'period_start' => $this->period_start?->toIso8601String(),
            'period_end' => $this->period_end?->toIso8601String(),
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'gross_revenue' => $this->gross_revenue,
            'order_count' => $this->order_count,
            'total_royalties' => $this->total_royalties,
            'status' => $this->status,
            'line_items' => RoyaltyLineItemResource::collection($this->whenLoaded('lineItems')),
        ];
    }
}
