<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\LeadPhaseEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin LeadPhaseEvent */
final class LeadPhaseEventResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_phase' => $this->from_phase,
            'to_phase' => $this->to_phase,
            'actor_user_id' => $this->actor_user_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
