<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\AiMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin AiMessage */
final class AiMessageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'role' => $this->role,
            'content' => $this->content,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
