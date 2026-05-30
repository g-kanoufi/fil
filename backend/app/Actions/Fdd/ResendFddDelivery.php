<?php

declare(strict_types=1);

namespace App\Actions\Fdd;

use App\Mail\FddDeliveryMail;
use App\Models\FddDelivery;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

final class ResendFddDelivery
{
    public function handle(FddDelivery $delivery, User $sender): FddDelivery
    {
        $delivery->load(['fdd', 'lead', 'recipient']);

        $recipient = $delivery->recipient ?? $delivery->lead?->prospect;
        $fdd = $delivery->fdd;

        if ($recipient !== null && filled($recipient->email) && $fdd !== null) {
            $documentId = $delivery->meta['document_id'] ?? 'n/a';

            Mail::to($recipient->email)->send(new FddDeliveryMail(
                fddTitle: (string) $fdd->title,
                documentId: (string) $documentId,
                deliveryId: (int) $delivery->id,
                isReminder: true,
            ));
        }

        $meta = $delivery->meta ?? [];
        $meta['last_resent_at'] = now()->toIso8601String();
        $meta['resent_by_user_id'] = $sender->id;
        $meta['resend_count'] = ((int) ($meta['resend_count'] ?? 0)) + 1;

        $delivery->update([
            'sent_at' => now(),
            'meta' => $meta,
        ]);

        return $delivery->fresh(['fdd', 'lead', 'recipient', 'latestSignature']);
    }
}
