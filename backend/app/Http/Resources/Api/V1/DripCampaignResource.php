<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\DripCampaign;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DripCampaign */
final class DripCampaignResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'status' => $this->status,
            'trigger_event' => $this->trigger_event,
            'steps' => DripStepResource::collection($this->whenLoaded('steps')),
            'steps_count' => $this->whenCounted('steps'),
            'enrollments_count' => $this->whenCounted('enrollments'),
            'updated_at' => $this->updated_at,
        ];
    }
}
