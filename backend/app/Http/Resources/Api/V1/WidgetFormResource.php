<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\WidgetForm;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin WidgetForm */
final class WidgetFormResource extends JsonResource
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
            'site_key' => $this->site_key,
            'entity' => $this->entity,
            'version' => $this->version,
            'status' => $this->status,
            'settings' => $this->settings ?? [],
            'fields' => WidgetFormFieldResource::collection($this->whenLoaded('formFields')),
        ];
    }
}
