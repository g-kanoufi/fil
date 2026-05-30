<?php

declare(strict_types=1);

namespace App\Support\Api;

use Illuminate\Http\JsonResponse;

final class ApiProblem
{
    public static function json(
        ApiErrorCode $code,
        string $message,
        int $status,
        array $extra = [],
    ): JsonResponse {
        return response()->json(array_merge([
            'message' => $message,
            'code' => $code->value,
        ], $extra), $status);
    }

    public static function unauthenticated(string $message = 'Unauthenticated.'): JsonResponse
    {
        return self::json(ApiErrorCode::Unauthenticated, $message, 401);
    }

    public static function staffRequired(string $message = 'This account cannot access the staff application.'): JsonResponse
    {
        return self::json(ApiErrorCode::StaffRequired, $message, 403);
    }

    public static function forbidden(string $message = 'Forbidden.'): JsonResponse
    {
        return self::json(ApiErrorCode::Forbidden, $message, 403);
    }
}
