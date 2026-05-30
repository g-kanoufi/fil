<?php

declare(strict_types=1);

namespace App\Actions\Communications;

use App\Jobs\Communications\SendStaffCommunicationJob;
use App\Mail\DripStepMail;
use App\Models\Communication;
use App\Models\Lead;
use App\Models\User;
use App\Services\Communications\CommunicationSuppressionService;
use App\Services\Sms\TwilioSmsService;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

final class SendStaffCommunication
{
    public function __construct(
        private readonly TwilioSmsService $twilio,
        private readonly CommunicationSuppressionService $suppressions,
    ) {}

    public function handle(
        Lead $lead,
        User $actor,
        string $channel,
        string $message,
        ?string $subject = null,
    ): Communication {
        $communication = $this->prepare($lead, $actor, $channel, $message, $subject);

        if (config('queue.default') === 'sync') {
            return $this->deliver($communication);
        }

        SendStaffCommunicationJob::dispatch($communication->id);

        return $communication->fresh() ?? $communication;
    }

    public function prepare(
        Lead $lead,
        User $actor,
        string $channel,
        string $message,
        ?string $subject = null,
    ): Communication {
        $lead->loadMissing('prospect');
        $prospect = $lead->prospect;

        if ($channel === 'email') {
            if (! filled($prospect?->email)) {
                throw ValidationException::withMessages([
                    'channel' => 'Lead prospect has no email address.',
                ]);
            }

            return Communication::query()->create([
                'lead_id' => $lead->id,
                'sender_user_id' => $actor->id,
                'sender_name' => $actor->name,
                'recipient_user_id' => $prospect?->id,
                'recipient_name' => $prospect?->name,
                'type' => 'email',
                'direction' => 'outbound',
                'message' => $message,
                'provider' => (string) config('mail.default', 'log'),
                'sent_at' => now(),
                'status' => 'pending',
                'meta' => [
                    'subject' => $subject ?? 'Message from FIL',
                    'staff_sent' => true,
                ],
            ]);
        }

        if ($channel === 'sms') {
            if (! filled($prospect?->phone)) {
                throw ValidationException::withMessages([
                    'channel' => 'Lead prospect has no phone number.',
                ]);
            }

            return Communication::query()->create([
                'lead_id' => $lead->id,
                'sender_user_id' => $actor->id,
                'sender_name' => $actor->name,
                'recipient_user_id' => $prospect?->id,
                'recipient_name' => $prospect?->name,
                'type' => 'sms',
                'direction' => 'outbound',
                'message' => $message,
                'provider' => 'twilio',
                'sent_at' => now(),
                'status' => 'pending',
                'meta' => [
                    'staff_sent' => true,
                ],
            ]);
        }

        throw ValidationException::withMessages([
            'channel' => 'Channel must be email or sms.',
        ]);
    }

    public function deliver(Communication $communication): Communication
    {
        if ($communication->status !== 'pending') {
            return $communication;
        }

        $communication->loadMissing('lead.prospect');
        $lead = $communication->lead;
        $prospect = $lead?->prospect;

        if ($lead === null) {
            $communication->update([
                'status' => 'failed',
                'errors' => 'Lead not found for communication.',
            ]);

            return $communication->fresh() ?? $communication;
        }

        if ($communication->type === 'email') {
            if (! filled($prospect?->email)) {
                $communication->update([
                    'status' => 'failed',
                    'errors' => 'Lead prospect has no email address.',
                ]);

                return $communication->fresh() ?? $communication;
            }

            if ($this->suppressions->isSuppressed('email', $prospect->email)) {
                $communication->update([
                    'status' => 'failed',
                    'errors' => 'Recipient email is suppressed (bounce or complaint).',
                ]);

                return $communication->fresh() ?? $communication;
            }

            /** @var array<string, mixed> $meta */
            $meta = $communication->meta ?? [];
            $subject = is_string($meta['subject'] ?? null) ? $meta['subject'] : 'Message from FIL';

            Mail::to($prospect->email)->send(new DripStepMail(
                $subject,
                $communication->message,
                communicationId: $communication->id,
            ));

            $communication->update([
                'status' => 'sent',
                'provider' => (string) config('mail.default', 'log'),
                'sent_at' => now(),
            ]);

            return $communication->fresh() ?? $communication;
        }

        if ($communication->type === 'sms') {
            if (! filled($prospect?->phone)) {
                $communication->update([
                    'status' => 'failed',
                    'errors' => 'Lead prospect has no phone number.',
                ]);

                return $communication->fresh() ?? $communication;
            }

            if ($this->suppressions->isSuppressed('sms', $prospect->phone)) {
                $communication->update([
                    'status' => 'failed',
                    'errors' => 'Recipient phone is suppressed.',
                ]);

                return $communication->fresh() ?? $communication;
            }

            $sid = $this->twilio->send($prospect->phone, $communication->message);

            $communication->update([
                'status' => 'sent',
                'provider' => $sid !== null ? 'twilio' : 'twilio_stub',
                'sent_at' => now(),
            ]);

            return $communication->fresh() ?? $communication;
        }

        $communication->update([
            'status' => 'failed',
            'errors' => 'Unsupported communication type.',
        ]);

        return $communication->fresh() ?? $communication;
    }
}
