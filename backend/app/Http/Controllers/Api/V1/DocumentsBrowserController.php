<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentRowResource;
use App\Models\Document;
use App\Models\DocumentLink;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentsBrowserController extends Controller
{
    public function settings(): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        return ApiResponse::payload([
            'enabled' => config('fil-documents.enabled', true),
            'enabled_entities' => config('fil-documents.enabled_entities', []),
            'document_types' => config('fil-documents.document_types', []),
            'labels' => config('fil-documents.labels', []),
        ]);
    }

    public function rows(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $entity = (string) $request->query('entity', 'store');
        $perPage = min(100, max(1, $request->integer('per_page', 50)));
        $cursor = max(0, $request->integer('cursor', 0));

        $linkableType = match ($entity) {
            'store', 'stores' => Store::class,
            'lead', 'application', 'applications' => Lead::class,
            'user', 'users' => User::class,
            'location', 'locations', 'franchise_location' => FranchiseLocation::class,
            default => null,
        };

        if ($linkableType === null) {
            return response()->json(['message' => 'Invalid entity.'], 422);
        }

        $allowedRoles = config('fil-documents.entity_document_types.'.$entity)
            ?? config('fil-documents.document_types', []);

        $query = DocumentLink::query()
            ->with(['document', 'linkable'])
            ->where('linkable_type', $linkableType)
            ->when(is_array($allowedRoles) && $allowedRoles !== [], fn ($inner) => $inner->whereIn('role', $allowedRoles))
            ->orderBy('id');

        if ($cursor > 0) {
            $query->where('id', '>', $cursor);
        }

        $links = $query->limit($perPage + 1)->get();
        $hasMore = $links->count() > $perPage;
        $page = $hasMore ? $links->slice(0, $perPage) : $links;
        $nextCursor = $hasMore ? $page->last()?->id : null;

        return response()->json([
            'data' => DocumentRowResource::collection($page),
            'meta' => [
                'next_cursor' => $nextCursor,
                'has_more' => $hasMore,
            ],
        ]);
    }
}
