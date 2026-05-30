<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Actions\Fdd\RecordFddSignature;
use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\SignatureResource;
use App\Models\FddDelivery;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class FddSignatureController extends Controller
{
    public function store(Request $request, FddDelivery $fddDelivery, RecordFddSignature $record): JsonResponse
    {
        $this->authorize('send', $fddDelivery->fdd);

        $validated = $request->validate([
            'signed_name' => ['required', 'string', 'max:255'],
            'agree' => ['accepted'],
        ]);

        $signature = $record->handle(
            $fddDelivery,
            $request->user(),
            [
                'signed_name' => $validated['signed_name'],
                'agree' => true,
            ],
        );

        return ApiResponse::resource(new SignatureResource($signature), 201);
    }
}
