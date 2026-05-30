<?php

declare(strict_types=1);

namespace App\Http\Resources\Api\V1;

use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin DocumentLink */
final class DocumentRowResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $document = $this->document;
        $linkable = $this->linkable;

        return [
            'id' => $this->id,
            'document_id' => $document?->id,
            'entity' => match ($this->linkable_type) {
                Store::class => 'store',
                Lead::class => 'lead',
                FranchiseLocation::class => 'location',
                default => 'user',
            },
            'entity_id' => $this->linkable_id,
            'entity_title' => $this->entityTitle($linkable),
            'doc_type' => $this->role,
            'doc_type_label' => config('fil-documents.labels.'.$this->role, $this->role),
            'title' => $document?->title,
            'file_name' => $document ? basename($document->storage_path) : null,
            'mime_type' => $document?->mime_type,
            'preview_url' => data_get($document?->extras, 'preview_url'),
            'download_url' => $document ? route('documents.download', $document, false) : null,
            'sort_order' => $this->sort_order,
        ];
    }

    private function entityTitle(mixed $linkable): ?string
    {
        if ($linkable === null) {
            return null;
        }

        return match (true) {
            $linkable instanceof Store, $linkable instanceof Lead, $linkable instanceof FranchiseLocation => $linkable->name ?? $linkable->title ?? null,
            method_exists($linkable, 'getAttribute') => (string) ($linkable->name ?? $linkable->email ?? ''),
            default => null,
        };
    }
}
