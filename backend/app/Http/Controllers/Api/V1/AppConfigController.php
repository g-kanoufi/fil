<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Domain\AppConfig;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\AppConfigResource;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class AppConfigController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $this->authorize('view', AppConfig::class);

        return ApiResponse::resource(new AppConfigResource(request()->user()));
    }
}
