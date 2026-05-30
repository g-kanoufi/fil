<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Closing;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Closing */
final class ClosingResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'lead_id' => $this->lead_id,
            'store_id' => $this->store_id,
            'area_id' => $this->area_id,
            'closing_date' => $this->closing_date?->toDateString(),
            'status' => $this->status,
        ];
    }
}
