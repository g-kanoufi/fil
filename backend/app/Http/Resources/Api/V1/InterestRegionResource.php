<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\InterestRegion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin InterestRegion */
final class InterestRegionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'parent_id' => $this->parent_id,
            'name' => $this->name,
            'code' => $this->code,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'legacy_term_id' => $this->legacy_term_id,
            'label' => $this->displayLabel(),
            'children' => self::collection($this->whenLoaded('children')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    private function displayLabel(): string
    {
        if ($this->relationLoaded('parent') && $this->parent !== null) {
            return sprintf('%s — %s', $this->parent->name, $this->name);
        }

        return $this->name;
    }
}
