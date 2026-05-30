<?php

declare(strict_types=1);

namespace App\Http\Controllers\Webhooks;

use App\Http\Controllers\Controller;
use App\Models\Communication;
use App\Models\NotificationDelivery;
use App\Services\Communications\CommunicationSuppressionService;
use App\Services\Mail\MailgunSignatureVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

final class MailgunWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        MailgunSignatureVerifier $verifier,
        CommunicationSuppressionService $suppressions,
    ): JsonResponse {
        if (! $verifier->verify($request)) {
            return response()->json(['error' => 'Invalid signature'], 403);
        }

        /** @var array<string, mixed> $event */
        $event = $request->input('event-data');

        if (! is_array($event)) {
            $raw = $request->input('event-data');

            if (is_string($raw)) {
                $decoded = json_decode($raw, true);
                $event = is_array($decoded) ? $decoded : [];
            } else {
                $event = $request->all();
            }
        }

        $eventName = (string) ($event['event'] ?? $request->input('event', ''));
        $messageId = $this->resolveMessageId($event);
        $deliveryId = $this->resolveDeliveryId($event);

        if ($deliveryId !== null) {
            $this->updateDelivery((int) $deliveryId, $eventName, $messageId, $event);
        } elseif ($messageId !== null) {
            $this->updateDeliveryByMessageId($messageId, $eventName, $event);
        }

        if ($messageId !== null) {
            $this->updateCommunication($messageId, $eventName, $event);
        }

        $this->maybeSuppressRecipient($eventName, $event, $suppressions);

        Log::info('Mailgun webhook received', [
            'event' => $eventName,
            'message_id' => $messageId,
            'delivery_id' => $deliveryId,
        ]);

        return response()->json(['received' => true]);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function resolveMessageId(array $event): ?string
    {
        $headers = $event['message']['headers'] ?? null;

        if (is_array($headers)) {
            $id = $headers['message-id'] ?? $headers['Message-Id'] ?? null;

            if (is_string($id) && $id !== '') {
                return trim($id, '<>');
            }
        }

        $id = $event['message']['headers']['message-id'] ?? null;

        return is_string($id) && $id !== '' ? trim($id, '<>') : null;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function resolveDeliveryId(array $event): ?int
    {
        $userVariables = $event['user-variables'] ?? $event['user_variables'] ?? null;

        if (is_array($userVariables)) {
            $id = $userVariables['fil_delivery_id'] ?? null;

            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        $headers = $event['message']['headers'] ?? null;

        if (is_array($headers)) {
            $header = $headers['x-fil-delivery-id'] ?? $headers['X-FIL-Delivery-Id'] ?? null;

            if (is_numeric($header)) {
                return (int) $header;
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function updateDelivery(int $deliveryId, string $eventName, ?string $messageId, array $event): void
    {
        $delivery = NotificationDelivery::query()->find($deliveryId);

        if ($delivery === null) {
            return;
        }

        $updates = [];

        if ($messageId !== null && $delivery->provider_message_id === null) {
            $updates['provider_message_id'] = $messageId;
        }

        $mappedStatus = $this->mapEventToDeliveryStatus($eventName);

        if ($mappedStatus !== null) {
            $updates['status'] = $mappedStatus;

            if ($mappedStatus === 'failed') {
                $updates['error'] = (string) ($event['delivery-status']['message']
                    ?? $event['reason']
                    ?? 'Mail delivery failed');
            }
        }

        if ($updates !== []) {
            $delivery->update($updates);
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private function updateDeliveryByMessageId(string $messageId, string $eventName, array $event): void
    {
        $delivery = NotificationDelivery::query()
            ->where('provider_message_id', $messageId)
            ->first();

        if ($delivery === null) {
            return;
        }

        $this->updateDelivery($delivery->id, $eventName, $messageId, $event);
    }

    /**
     * @param array<string, mixed> $event
     */
    private function updateCommunication(string $messageId, string $eventName, array $event): void
    {
        $communication = Communication::query()
            ->where('external_message_id', $messageId)
            ->first();

        if ($communication === null) {
            return;
        }

        $mappedStatus = $this->mapEventToCommunicationStatus($eventName);

        if ($mappedStatus === null) {
            return;
        }

        $updates = ['status' => $mappedStatus];

        if ($mappedStatus === 'failed') {
            $updates['errors'] = (string) ($event['delivery-status']['message']
                ?? $event['reason']
                ?? 'Mail delivery failed');
        }

        $communication->update($updates);
    }

    private function mapEventToDeliveryStatus(string $eventName): ?string
    {
        return match ($eventName) {
            'delivered' => 'delivered',
            'failed', 'rejected' => 'failed',
            'complained' => 'complained',
            default => null,
        };
    }

    private function mapEventToCommunicationStatus(string $eventName): ?string
    {
        return match ($eventName) {
            'delivered' => 'delivered',
            'failed', 'rejected' => 'failed',
            'complained' => 'complained',
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function maybeSuppressRecipient(
        string $eventName,
        array $event,
        CommunicationSuppressionService $suppressions,
    ): void {
        if (! in_array($eventName, ['failed', 'rejected', 'complained'], true)) {
            return;
        }

        $recipient = (string) ($event['recipient'] ?? '');

        if ($recipient === '') {
            return;
        }

        $reason = $eventName === 'complained' ? 'complained' : 'bounce';

        $suppressions->suppress(
            channel: 'email',
            address: $recipient,
            reason: $reason,
            source: 'mailgun_webhook',
            meta: [
                'event' => $eventName,
                'reason_detail' => $event['reason'] ?? $event['delivery-status']['message'] ?? null,
            ],
        );
    }
}
