<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FddDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FddDelivery */
final class FddDeliveryResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fdd_id' => $this->fdd_id,
            'lead_id' => $this->lead_id,
            'recipient_user_id' => $this->recipient_user_id,
            'sent_at' => $this->sent_at?->toIso8601String(),
            'status' => $this->status,
            'delivery_method' => $this->delivery_method,
            'document_id' => $this->meta['document_id'] ?? null,
            'resend_count' => (int) ($this->meta['resend_count'] ?? 0),
            'last_resent_at' => $this->meta['last_resent_at'] ?? null,
            'signature_status' => $this->whenLoaded('latestSignature', fn () => $this->latestSignature?->status),
            'signed_at' => $this->whenLoaded('latestSignature', fn () => $this->latestSignature?->signed_at?->toIso8601String()),
            'signed_name' => $this->whenLoaded('latestSignature', fn () => $this->latestSignature?->signature_data['signed_name'] ?? null),
            'fdd' => new FddResource($this->whenLoaded('fdd')),
            'lead' => $this->whenLoaded('lead', fn () => [
                'id' => $this->lead?->id,
                'title' => $this->lead?->title,
                'lead_fdd_status' => $this->lead?->lead_fdd_status,
            ]),
            'recipient' => $this->whenLoaded('recipient', fn () => [
                'id' => $this->recipient?->id,
                'name' => $this->recipient?->name,
                'email' => $this->recipient?->email,
            ]),
        ];
    }
}
