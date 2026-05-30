<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WidgetFormField;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WidgetFormField */
final class WidgetFormFieldResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'field_id' => $this->field_id,
            'sort_order' => $this->sort_order,
            'label_override' => $this->label_override,
            'placeholder' => $this->placeholder,
            'required_override' => $this->required_override,
            'width' => $this->width,
            'status' => $this->status,
            'field' => new FieldResource($this->whenLoaded('field')),
        ];
    }
}
