<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fdd\BulkSendFddDeliveries;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\BulkSendFddRequest;
use App\Models\Fdd;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;

final class FddBulkSendController extends Controller
{
    public function store(BulkSendFddRequest $request, BulkSendFddDeliveries $bulkSend): JsonResponse
    {
        $this->authorize('create', Fdd::class);

        $result = $bulkSend->handle(
            $request->leadIds(),
            $request->fddType(),
            $request->user(),
        );

        return ApiResponse::payload($result, 201);
    }
}
