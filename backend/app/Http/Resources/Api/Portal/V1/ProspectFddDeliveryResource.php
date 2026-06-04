<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Portal\V1;

use App\Models\FddDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FddDelivery */
final class ProspectFddDeliveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'fdd' => [
                'id' => $this->fdd?->id,
                'title' => $this->fdd?->title,
            ],
            'can_sign' => $this->status !== 'signed',
        ];
    }
}
