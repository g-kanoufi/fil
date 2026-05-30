<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Fdd;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Fdd */
final class FddResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'title' => $this->title,
            'slug' => $this->slug,
            'area_id' => $this->area_id,
            'document_id' => $this->document_id,
            'version' => $this->version,
            'status' => $this->status,
            'deliveries_count' => $this->when(isset($this->deliveries_count), $this->deliveries_count),
            'area' => $this->whenLoaded('area', fn () => [
                'id' => $this->area?->id,
                'name' => $this->area?->name,
            ]),
            'document' => $this->whenLoaded('document', fn () => [
                'id' => $this->document?->id,
                'title' => $this->document?->title,
            ]),
        ];
    }
}
