<?php

declare(strict_types=1);

namespace App\Jobs\Notifications;

use App\Mail\NotificationMail;
use App\Models\Communication;
use App\Models\NotificationDelivery;
use App\Models\NotificationLog;
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

final class SendNotificationEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;

    /** @var list<int> */
    public array $backoff = [10, 30, 60, 120, 300];

    public function __construct(
        public readonly int $deliveryId,
        public readonly string $bodyHtml,
    ) {}

    public function handle(OutboundMailGuard $mailGuard): void
    {
        $delivery = NotificationDelivery::query()->find($this->deliveryId);

        if ($delivery === null || $delivery->status === 'sent') {
            return;
        }

        $intendedEmail = trim((string) ($delivery->recipient_email ?? ''));

        if ($intendedEmail === '') {
            $delivery->update([
                'status' => 'failed',
                'error' => 'Missing recipient email',
            ]);

            return;
        }

        $email = $mailGuard->resolveRecipients([$intendedEmail])[0];

        try {
            Mail::to($email)->send(new NotificationMail(
                subjectLine: (string) $delivery->subject,
                bodyHtml: $this->bodyHtml,
                deliveryId: $delivery->id,
            ));

            $delivery->update([
                'status' => 'sent',
                'sent_at' => now(),
                'provider' => config('mail.default'),
                'recipient_email' => $email,
            ]);

            if ($delivery->lead_id !== null) {
                Communication::query()->create([
                    'lead_id' => $delivery->lead_id,
                    'recipient_user_id' => $delivery->recipient_user_id,
                    'recipient_name' => $delivery->meta['recipient_name'] ?? null,
                    'type' => 'email',
                    'direction' => 'outbound',
                    'message' => strip_tags($this->bodyHtml),
                    'provider' => config('mail.default'),
                    'sent_at' => now(),
                    'status' => 'sent',
                    'meta' => [
                        'notification_delivery_id' => $delivery->id,
                        'notification_rule_id' => $delivery->notification_rule_id,
                        'subject' => $delivery->subject,
                    ],
                ]);
            }

            NotificationLog::query()->create([
                'notification_rule_id' => $delivery->notification_rule_id,
                'type' => 'sent',
                'component' => $delivery->trigger_slug,
                'message' => 'Email sent to '.$email,
                'logged_at' => now(),
                'meta' => ['delivery_id' => $delivery->id],
            ]);
        } catch (\Throwable $exception) {
            $delivery->update([
                'status' => 'failed',
                'error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }
}
