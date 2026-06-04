<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Portal\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Portal\V1\SignProspectFddRequest;
use App\Http\Resources\Api\Portal\V1\ProspectFddDeliveryResource;
use App\Models\FddDelivery;
use App\Services\ESign\ESignService;
use App\Services\Portal\ProspectFddService;
use App\Support\Api\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ProspectFddController extends Controller
{
    public function index(Request $request, ProspectFddService $fdd): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null, 403);

        return ApiResponse::collection(
            ProspectFddDeliveryResource::collection($fdd->deliveriesForProspect($user)),
        );
    }

    public function signSession(
        Request $request,
        FddDelivery $fddDelivery,
        ProspectFddService $fdd,
        ESignService $esign,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $delivery = $fdd->deliveryForProspect($user, $fddDelivery);

        if ($delivery->status === 'signed') {
            return ApiResponse::message('This FDD is already signed.');
        }

        $session = $esign->beginProspectSigning($delivery, $user);

        return ApiResponse::payload($session);
    }

    public function sign(
        SignProspectFddRequest $request,
        FddDelivery $fddDelivery,
        ProspectFddService $fdd,
        ESignService $esign,
    ): JsonResponse {
        $user = $request->user();
        abort_unless($user !== null, 403);

        $delivery = $fdd->deliveryForProspect($user, $fddDelivery);
        $vendorReference = $request->vendorReference();

        if ($vendorReference !== null) {
            $pending = $delivery->signatures()
                ->where('status', 'pending')
                ->where('vendor_reference', $vendorReference)
                ->firstOrFail();

            $signature = $esign->completeVendorSign(
                $pending,
                $user,
                $request->signedName(),
            );
        } else {
            $signature = $esign->completeLocalSign($delivery, $user, [
                'signed_name' => $request->signedName(),
                'agree' => true,
            ]);
        }

        return ApiResponse::payload([
            'id' => $signature->id,
            'status' => $signature->status,
            'signed_at' => $signature->signed_at?->toIso8601String(),
        ], 201);
    }
}
