<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;

final class ApiResponse
{
    public static function resource(JsonResource $resource, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $resource,
        ], $status);
    }

    public static function collection(AnonymousResourceCollection $collection, int $status = 200): JsonResponse
    {
        return response()->json([
            'data' => $collection,
        ], $status);
    }

    public static function message(string $message, int $status = 200, array $extra = []): JsonResponse
    {
        return response()->json([
            'data' => array_merge(['message' => $message], $extra),
        ], $status);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public static function payload(array $payload, int $status = 200): JsonResponse
    {
        return response()->json(['data' => $payload], $status);
    }
}
