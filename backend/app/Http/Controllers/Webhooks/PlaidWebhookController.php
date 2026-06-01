<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Ach\PlaidWebhookHandler;
use App\Services\Ach\PlaidWebhookVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class PlaidWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        PlaidWebhookVerifier $verifier,
        PlaidWebhookHandler $handler,
    ): JsonResponse {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid Plaid webhook signature'], 403);
        }

        /** @var array<string, mixed>|null $payload */
        $payload = json_decode($request->getContent(), true);

        if (! is_array($payload)) {
            return response()->json(['error' => 'Invalid payload'], 422);
        }

        Log::info('Plaid webhook received', [
            'webhook_type' => $payload['webhook_type'] ?? null,
            'webhook_code' => $payload['webhook_code'] ?? null,
        ]);

        $handler->handle($payload);

        return response()->json(['received' => true]);
    }
}
