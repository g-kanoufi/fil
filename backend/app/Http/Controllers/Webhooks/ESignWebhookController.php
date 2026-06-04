<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\ESign\ESignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class ESignWebhookController extends Controller
{
    public function __invoke(Request $request, ESignService $esign): JsonResponse
    {
        $payload = $request->all();
        $pending = $esign->client()->completeFromWebhook($payload);

        if ($pending === null) {
            return response()->json(['received' => true]);
        }

        $pending->load(['signer', 'fddDelivery']);
        $signer = $pending->signer;

        if ($signer === null) {
            return response()->json(['received' => true]);
        }

        $esign->completeVendorSign($pending, $signer);

        return response()->json(['received' => true]);
    }
}
