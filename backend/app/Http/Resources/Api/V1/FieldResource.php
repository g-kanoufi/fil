<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Field;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Field */
final class FieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'key' => $this->key,
            'name' => $this->name,
            'type' => $this->type,
            'entity' => $this->entity,
            'storage' => $this->storage,
            'maps_to_column' => $this->maps_to_column,
            'config' => $this->config ?? [],
            'required' => $this->required,
            'is_filterable' => $this->is_filterable,
            'is_sortable' => $this->is_sortable,
            'is_facetable' => $this->is_facetable,
            'sort_order' => $this->sort_order,
            'status' => $this->status,
            'field_group_id' => $this->field_group_id,
        ];
    }
}
