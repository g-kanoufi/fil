<?php

declare(strict_types=1);

namespace App\Actions\Communications;

use App\Models\Communication;
use App\Services\Communications\InboundCommunicationMatch;

final class RecordInboundCommunication
{
    public function handle(
        string $type,
        string $message,
        InboundCommunicationMatch $match,
        string $provider,
        ?string $externalMessageId = null,
        ?string $senderLabel = null,
        array $meta = [],
    ): Communication {
        return Communication::query()->create([
            'lead_id' => $match->leadId,
            'sender_name' => $match->senderName ?? $senderLabel,
            'recipient_user_id' => $match->contactUserId,
            'type' => $type,
            'direction' => 'inbound',
            'message' => $message,
            'provider' => $provider,
            'status' => 'received',
            'sent_at' => now(),
            'external_message_id' => $externalMessageId,
            'meta' => [...$meta, 'inbound' => true],
        ]);
    }
}
