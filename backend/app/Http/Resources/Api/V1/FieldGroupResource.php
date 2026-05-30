<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\FieldGroup;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin FieldGroup */
final class FieldGroupResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'title' => $this->title,
            'slug' => $this->slug,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'fields' => FieldResource::collection($this->whenLoaded('fields')),
        ];
    }
}
