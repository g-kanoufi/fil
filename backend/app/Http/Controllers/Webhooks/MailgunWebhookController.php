<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Services\Communications\CommunicationWebhookService;
use App\Services\Mail\MailgunSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class MailgunWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MailgunSignatureVerifier $verifier,
        CommunicationWebhookService $webhooks,
    ): JsonResponse {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        /** @var array<string, mixed> $event */
        $event = $this->parseEventPayload($request);
        $eventName = (string) ($event['event'] ?? $request->input('event', ''));

        $webhooks->handleMailgunEvent($event);

        Log::info('Mailgun webhook received', [
            'event' => $eventName,
            'event_id' => $event['id'] ?? null,
        ]);

        return response()->json(['received' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseEventPayload(Request $request): array
    {
        /** @var array<string, mixed>|null $event */
        $event = $request->input('event-data');

        if (is_array($event)) {
            return $event;
        }

        $raw = $request->input('event-data');

        if (is_string($raw)) {
            $decoded = json_decode($raw, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $request->all();
    }
}
