<?php

declare(strict_types=1);

namespace App\Services\Communications;

use App\Models\Communication;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\WebhookEvent;
use App\Services\Activity\ActivityRecorder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Str;

final class CommunicationWebhookService
{
    public function __construct(
        private readonly ActivityRecorder $activity,
        private readonly CommunicationSuppressionService $suppressions,
    ) {}

    /**
     * @param  array<string, mixed>  $event
     */
    public function handleMailgunEvent(array $event): void
    {
        $eventName = (string) ($event['event'] ?? '');
        $eventId = (string) ($event['id'] ?? '');

        if ($eventId !== '' && $this->isDuplicate('mailgun', $eventId)) {
            return;
        }

        $messageId = $this->resolveMailgunMessageId($event);
        $deliveryId = $this->resolveMailgunDeliveryId($event);

        if ($deliveryId !== null) {
            $this->updateNotificationDelivery($deliveryId, $eventName, $messageId, $event);
        } elseif ($messageId !== null) {
            $this->updateNotificationDeliveryByMessageId($messageId, $eventName, $event);
        }

        if ($messageId !== null) {
            $this->applyMappedEvent(
                mapKey: "fil-comm-webhooks.mailgun.events.{$eventName}",
                providerEvent: $eventName,
                context: $event,
                source: 'mailgun_webhook',
                resolveCommunication: fn (): ?Communication => Communication::query()
                    ->where('external_message_id', $messageId)
                    ->first(),
            );
        }

        $this->maybeSuppressMailgunRecipient($eventName, $event);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function handleTwilioStatus(string $messageSid, string $messageStatus, array $meta = []): void
    {
        $status = strtolower($messageStatus);

        if ($status === '') {
            return;
        }

        $dedupeKey = "{$messageSid}:{$status}";

        if ($this->isDuplicate('twilio', $dedupeKey)) {
            return;
        }

        $this->applyMappedEvent(
            mapKey: "fil-comm-webhooks.twilio.statuses.{$status}",
            providerEvent: $status,
            context: $meta,
            source: 'twilio_webhook',
            resolveCommunication: fn (): ?Communication => Communication::query()
                ->where('external_message_id', $messageSid)
                ->first(),
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function applyMappedEvent(
        string $mapKey,
        string $providerEvent,
        array $context,
        string $source,
        callable $resolveCommunication,
    ): void {
        /** @var array{communication_status?: ?string, activity_action?: string, meta_key?: string}|null $mapping */
        $mapping = config($mapKey);

        if (! is_array($mapping) || ! isset($mapping['activity_action'])) {
            return;
        }

        $communication = $resolveCommunication();

        if ($communication === null) {
            return;
        }

        $previousStatus = $communication->status;
        $updates = [];

        $communicationStatus = $mapping['communication_status'] ?? null;

        if (is_string($communicationStatus) && $communicationStatus !== '') {
            $updates['status'] = $communicationStatus;

            if ($communicationStatus === 'failed') {
                $updates['errors'] = $this->resolveFailureDetail($providerEvent, $context);
            }
        }

        $metaKey = $mapping['meta_key'] ?? null;

        if (is_string($metaKey) && $metaKey !== '') {
            $meta = $communication->meta ?? [];
            $meta[$metaKey] = now()->toIso8601String();
            $meta['last_provider_event'] = $providerEvent;
            $updates['meta'] = $meta;
        }

        if ($updates !== []) {
            $communication->update($updates);
            $communication->refresh();
        }

        $action = (string) $mapping['activity_action'];

        if ($this->shouldRecordActivity($action, $previousStatus, $communication->status)) {
            $this->recordCommunicationActivity($communication, $action, $providerEvent, $context, $source);
        }
    }

    private function shouldRecordActivity(string $action, ?string $previousStatus, ?string $newStatus): bool
    {
        if (in_array($action, ['opened', 'clicked'], true)) {
            return true;
        }

        return $newStatus !== $previousStatus;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recordCommunicationActivity(
        Communication $communication,
        string $action,
        string $providerEvent,
        array $context,
        string $source,
    ): void {
        $channel = strtoupper((string) $communication->type);
        $recipient = $communication->recipient_name ?? 'recipient';
        $summary = match ($action) {
            'delivered' => "{$channel} delivered to {$recipient}",
            'failed' => "{$channel} delivery failed for {$recipient}",
            'opened' => "Email opened by {$recipient}",
            'clicked' => "Email link clicked by {$recipient}",
            'read' => "SMS read by {$recipient}",
            default => "{$channel} {$action} ({$providerEvent}) for {$recipient}",
        };

        $subject = $communication->lead_id !== null
            ? Lead::query()->find($communication->lead_id)
            : null;

        $this->activity->record(
            category: 'comm',
            action: $action,
            summary: $summary,
            actor: null,
            subject: $subject,
            object: $communication,
            payload: [
                'communication_id' => $communication->id,
                'provider_event' => $providerEvent,
                'external_message_id' => $communication->external_message_id,
            ],
            source: $source,
        );
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function updateNotificationDelivery(int $deliveryId, string $eventName, ?string $messageId, array $event): void
    {
        $delivery = NotificationDelivery::query()->find($deliveryId);

        if ($delivery === null) {
            return;
        }

        $updates = [];

        if ($messageId !== null && $delivery->provider_message_id === null) {
            $updates['provider_message_id'] = $messageId;
        }

        $mappedStatus = match ($eventName) {
            'delivered' => 'delivered',
            'failed', 'rejected' => 'failed',
            'complained' => 'complained',
            default => null,
        };

        if ($mappedStatus !== null) {
            $updates['status'] = $mappedStatus;

            if ($mappedStatus === 'failed') {
                $updates['error'] = $this->resolveFailureDetail($eventName, $event);
            }
        }

        if ($updates !== []) {
            $delivery->update($updates);
        }
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function updateNotificationDeliveryByMessageId(string $messageId, string $eventName, array $event): void
    {
        $delivery = NotificationDelivery::query()
            ->where('provider_message_id', $messageId)
            ->first();

        if ($delivery === null) {
            return;
        }

        $this->updateNotificationDelivery($delivery->id, $eventName, $messageId, $event);
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function maybeSuppressMailgunRecipient(string $eventName, array $event): void
    {
        if (! in_array($eventName, ['failed', 'rejected', 'complained'], true)) {
            return;
        }

        $recipient = (string) ($event['recipient'] ?? '');

        if ($recipient === '') {
            return;
        }

        $reason = $eventName === 'complained' ? 'complained' : 'bounce';

        $this->suppressions->suppress(
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

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveFailureDetail(string $eventName, array $context): string
    {
        return (string) ($context['delivery-status']['message']
            ?? $context['reason']
            ?? $context['ErrorMessage']
            ?? 'Delivery failed');
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function resolveMailgunMessageId(array $event): ?string
    {
        $headers = $event['message']['headers'] ?? null;

        if (! is_array($headers)) {
            return null;
        }

        $id = $headers['message-id'] ?? $headers['Message-Id'] ?? null;

        return is_string($id) && $id !== '' ? trim($id, '<>') : null;
    }

    /**
     * @param  array<string, mixed>  $event
     */
    private function resolveMailgunDeliveryId(array $event): ?int
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

    private function isDuplicate(string $provider, string $externalEventId): bool
    {
        try {
            WebhookEvent::query()->create([
                'provider' => $provider,
                'external_event_id' => Str::limit($externalEventId, 191, ''),
            ]);

            return false;
        } catch (QueryException) {
            return true;
        }
    }
}
