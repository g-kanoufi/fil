<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\DocumentResource;
use App\Models\Document;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class DocumentController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Document::class);

        $query = Document::query()
            ->with('uploader:id,name,email')
            ->orderByDesc('updated_at');

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where('title', 'like', '%'.$search.'%');
        }

        $documents = $query->limit(200)->get();

        return ApiResponse::collection(DocumentResource::collection($documents));
    }

    public function show(Document $document): JsonResponse
    {
        $this->authorize('view', $document);

        $document->load('uploader:id,name,email');

        return ApiResponse::resource(new DocumentResource($document));
    }
}
