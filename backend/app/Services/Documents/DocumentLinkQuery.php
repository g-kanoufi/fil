<?php

declare(strict_types=1);

namespace App\Services\Documents;

use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class DocumentLinkQuery
{
    public function linkableTypeForEntity(string $entity): ?string
    {
        return match ($entity) {
            'store', 'stores' => Store::class,
            'lead', 'application', 'applications' => Lead::class,
            'user', 'users' => User::class,
            'location', 'locations', 'franchise_location' => FranchiseLocation::class,
            default => null,
        };
    }

    /**
     * @return Builder<DocumentLink>
     */
    public function baseQuery(string $entity): Builder
    {
        $linkableType = $this->linkableTypeForEntity($entity);

        if ($linkableType === null) {
            throw new \InvalidArgumentException('Invalid entity.');
        }

        $allowedRoles = config('fil-documents.entity_document_types.'.$entity)
            ?? config('fil-documents.document_types', []);

        return DocumentLink::query()
            ->with([
                'document',
                'linkable' => fn ($morphTo) => $morphTo->morphWith([
                    FranchiseLocation::class => ['store'],
                ]),
            ])
            ->where('linkable_type', $linkableType)
            ->when(is_array($allowedRoles) && $allowedRoles !== [], fn (Builder $inner) => $inner->whereIn('role', $allowedRoles))
            ->orderBy('id');
    }

    /**
     * @return Collection<int, DocumentLink>
     */
    public function allForEntity(string $entity, ?string $docType = null): Collection
    {
        $query = $this->baseQuery($entity);

        if ($docType !== null && $docType !== '') {
            $query->where('role', $docType);
        }

        /** @var Collection<int, DocumentLink> $links */
        $links = $query->get();

        return $links;
    }
}
