<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\RoyaltyLineItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin RoyaltyLineItem */
final class RoyaltyLineItemResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'royalty_type' => $this->royalty_type,
            'royalty_name' => $this->royalty_name,
            'gross_revenue' => $this->gross_revenue,
            'royalty_rate' => $this->royalty_rate,
            'royalty_amount' => $this->royalty_amount,
            'payment_status' => $this->payment_status,
        ];
    }
}
