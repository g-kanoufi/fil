<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin array{entity: string, groups: mixed, hidden_field_keys: list<string>, readonly_field_keys: list<string>} */
final class FieldSchemaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'entity' => $this->resource['entity'],
            'groups' => FieldGroupResource::collection($this->resource['groups']),
            'hidden_field_keys' => $this->resource['hidden_field_keys'],
            'readonly_field_keys' => $this->resource['readonly_field_keys'],
        ];
    }
}
