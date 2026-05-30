<?php

declare(strict_types=1);

namespace App\Jobs\Drips;

use App\Mail\DripStepMail;
use App\Models\Communication;
use App\Models\DripStepRun;
use App\Models\Lead;
use App\Models\User;
use App\Services\Drips\DripEnrollmentService;
use App\Services\Sms\TwilioSmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

final class SendDripStepJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly int $dripStepRunId,
    ) {}

    public function handle(TwilioSmsService $twilio, DripEnrollmentService $drips): void
    {
        $run = DripStepRun::query()
            ->with(['step', 'enrollment.lead.prospect', 'enrollment.campaign'])
            ->find($this->dripStepRunId);

        if ($run === null || $run->status !== 'pending') {
            return;
        }

        $enrollment = $run->enrollment;
        $lead = $enrollment?->lead;
        $step = $run->step;
        $prospect = $lead?->prospect;

        if ($lead === null || $step === null || $enrollment === null) {
            $run->update(['status' => 'failed', 'error' => 'Missing lead or step']);

            return;
        }

        if ($enrollment->status !== 'active') {
            $run->update(['status' => 'cancelled', 'error' => 'Enrollment is not active']);

            return;
        }

        $message = $step->body_template ?? 'Drip step '.$step->sort_order;
        $communication = $this->deliverStep($step->channel, $lead, $prospect, $step->subject, $message, $step->id, $twilio);

        if ($communication === null) {
            $run->update([
                'status' => 'failed',
                'error' => 'Unable to deliver '.$step->channel.' message',
            ]);

            return;
        }

        $run->update([
            'status' => 'sent',
            'sent_at' => now(),
            'communication_id' => $communication->id,
        ]);

        Log::info('Drip step sent', [
            'lead_id' => $lead->id,
            'step_id' => $step->id,
            'channel' => $step->channel,
        ]);

        $drips->advanceAfterStep($enrollment, $step);
    }

    private function deliverStep(
        string $channel,
        Lead $lead,
        ?User $prospect,
        ?string $subject,
        string $message,
        int $stepId,
        TwilioSmsService $twilio,
    ): ?Communication {
        $provider = (string) config('mail.default', 'log');

        if ($channel === 'email') {
            if (! filled($prospect?->email)) {
                return null;
            }

            $communication = Communication::query()->create([
                'lead_id' => $lead->id,
                'recipient_user_id' => $prospect?->id,
                'recipient_name' => $prospect?->name,
                'type' => 'email',
                'direction' => 'outbound',
                'message' => $message,
                'provider' => $provider,
                'sent_at' => now(),
                'status' => 'sent',
                'meta' => [
                    'subject' => $subject,
                    'drip_step_id' => $stepId,
                ],
            ]);

            Mail::to($prospect->email)->send(new DripStepMail(
                $subject ?? 'Message from FIL',
                $message,
                communicationId: $communication->id,
            ));

            return $communication;
        }

        if ($channel === 'sms') {
            if (! filled($prospect?->phone)) {
                return null;
            }

            $sid = $twilio->send($prospect->phone, $message);

            return Communication::query()->create([
                'lead_id' => $lead->id,
                'recipient_user_id' => $prospect?->id,
                'recipient_name' => $prospect?->name,
                'type' => 'sms',
                'direction' => 'outbound',
                'message' => $message,
                'provider' => $sid !== null ? 'twilio' : 'twilio_stub',
                'sent_at' => now(),
                'status' => 'sent',
                'meta' => [
                    'subject' => $subject,
                    'drip_step_id' => $stepId,
                ],
            ]);
        }

        return null;
    }
}
