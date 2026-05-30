<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Communication;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Communication */
final class CommunicationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'type' => $this->type,
            'direction' => $this->direction,
            'message' => $this->message,
            'status' => $this->status,
            'provider' => $this->provider,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'recipient_name' => $this->recipient_name,
        ];
    }
}
