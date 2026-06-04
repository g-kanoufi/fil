<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Area;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Area */
final class AreaResource extends JsonResource
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
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'territory' => $this->territory,
            'store_count' => $this->whenCounted('stores'),
        ];
    }
}
