<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Public\V1;

use App\Http\Controllers\Controller;
use App\Services\App\ClientOptionsService;
use Illuminate\Http\JsonResponse;

final class BrandingController extends Controller
{
    public function __construct(
        private readonly ClientOptionsService $options,
    ) {}

    public function __invoke(): JsonResponse
    {
        return response()->json([
            'data' => $this->options->publicBranding(),
        ]);
    }
}
