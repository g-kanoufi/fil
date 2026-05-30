<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Ach\PlaidWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PlaidWebhookController extends Controller
{
    public function __invoke(Request $request, PlaidWebhookVerifier $verifier): JsonResponse
    {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid Plaid webhook signature'], 403);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($request->getContent(), true);

        Log::info('Plaid webhook received', [
            'webhook_type' => is_array($payload) ? ($payload['webhook_type'] ?? null) : null,
            'webhook_code' => is_array($payload) ? ($payload['webhook_code'] ?? null) : null,
        ]);

        return response()->json(['received' => true]);
    }
}
