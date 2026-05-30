<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\Document;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Document */
final class DocumentResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'mime_type' => $this->mime_type,
            'file_size' => $this->file_size,
            'status' => $this->status,
            'version' => $this->version,
            'uploaded_by_user_id' => $this->uploaded_by_user_id,
            'uploader_name' => $this->whenLoaded('uploader', fn () => $this->uploader?->name),
            'download_url' => route('documents.download', $this->resource, false),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
