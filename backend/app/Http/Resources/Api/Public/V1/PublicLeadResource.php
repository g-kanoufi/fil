<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\Public\V1;

use App\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
final class PublicLeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'message' => 'Thank you for your application.',
            'title' => $this->title,
        ];
    }
}
