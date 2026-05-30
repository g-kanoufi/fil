<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Models\Communication;
use App\Models\NotificationDelivery;
use Illuminate\Mail\Events\MessageSent;

final class RecordFilMailDeliveryMetadata
{
    public function handle(MessageSent $event): void
    {
        $messageId = $event->sent->getMessageId();

        if ($messageId === null) {
            return;
        }

        $normalizedId = trim($messageId, '<>');
        $headers = $event->message->getHeaders();
        $deliveryHeader = $headers->get('X-FIL-Delivery-Id');
        $communicationHeader = $headers->get('X-FIL-Communication-Id');

        if ($deliveryHeader !== null) {
            $deliveryId = (int) $deliveryHeader->getBodyAsString();

            if ($deliveryId > 0) {
                NotificationDelivery::query()
                    ->whereKey($deliveryId)
                    ->whereNull('provider_message_id')
                    ->update([
                        'provider_message_id' => $normalizedId,
                        'provider' => config('mail.default'),
                    ]);
            }
        }

        if ($communicationHeader !== null) {
            $communicationId = (int) $communicationHeader->getBodyAsString();

            if ($communicationId > 0) {
                Communication::query()
                    ->whereKey($communicationId)
                    ->whereNull('external_message_id')
                    ->update([
                        'external_message_id' => $normalizedId,
                        'provider' => config('mail.default'),
                    ]);
            }
        }
    }
}
