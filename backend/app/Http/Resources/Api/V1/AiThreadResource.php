<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiThread;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiThread */
final class AiThreadResource extends JsonResource
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
            'status' => $this->status,
            'updated_at' => $this->updated_at?->toIso8601String(),
            'messages' => AiMessageResource::collection($this->whenLoaded('messages')),
        ];
    }
}
