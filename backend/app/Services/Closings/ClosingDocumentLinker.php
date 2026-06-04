<?php

declare(strict_types=1);

namespace App\Services\Closings;

use App\Models\Closing;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ClosingDocumentLinker
{
    /**
     * @return list<array{id: int, title: string, role: string|null}>
     */
    public function linkedDocuments(Closing $closing): array
    {
        return DocumentLink::query()
            ->where('linkable_type', Closing::class)
            ->where('linkable_id', $closing->id)
            ->with('document:id,title')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (DocumentLink $link): array => [
                'id' => (int) $link->document_id,
                'title' => (string) ($link->document?->title ?? "Document #{$link->document_id}"),
                'role' => $link->role,
            ])
            ->all();
    }

    /**
     * @param  list<int>  $documentIds
     */
    public function sync(Closing $closing, array $documentIds, User $actor): void
    {
        $documentIds = array_values(array_unique(array_map(intval(...), $documentIds)));

        $allowedIds = Document::query()
            ->whereIn('id', $documentIds)
            ->get()
            ->filter(fn (Document $document): bool => Gate::forUser($actor)->allows('view', $document))
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($closing, $allowedIds): void {
            DocumentLink::query()
                ->where('linkable_type', Closing::class)
                ->where('linkable_id', $closing->id)
                ->whereNotIn('document_id', $allowedIds)
                ->delete();

            foreach ($allowedIds as $index => $documentId) {
                DocumentLink::query()->updateOrCreate(
                    [
                        'document_id' => $documentId,
                        'linkable_type' => Closing::class,
                        'linkable_id' => $closing->id,
                        'role' => 'closing_agreement',
                    ],
                    ['sort_order' => ($index + 1) * 10],
                );
            }
        });
    }
}
