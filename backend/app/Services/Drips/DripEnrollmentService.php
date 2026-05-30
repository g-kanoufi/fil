<?php

declare(strict_types=1);

namespace App\Services\Drips;

use App\Jobs\Drips\SendDripStepJob;
use App\Models\DripCampaign;
use App\Models\DripEnrollment;
use App\Models\DripStep;
use App\Models\DripStepRun;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;

final class DripEnrollmentService
{
    public function enroll(Lead $lead, ?string $triggerEvent = 'lead_created'): ?DripEnrollment
    {
        $campaign = $this->resolveCampaign($lead, $triggerEvent);

        if ($campaign === null) {
            return null;
        }

        if (DripEnrollment::query()->where('lead_id', $lead->id)->where('drip_campaign_id', $campaign->id)->exists()) {
            return null;
        }

        return DB::transaction(function () use ($lead, $campaign): DripEnrollment {
            $lead->update(['drip_campaign_id' => $campaign->id]);

            $enrollment = DripEnrollment::query()->create([
                'drip_campaign_id' => $campaign->id,
                'lead_id' => $lead->id,
                'status' => 'active',
            ]);

            $firstStep = $campaign->steps()->where('status', 'active')->orderBy('sort_order')->first();

            if ($firstStep !== null) {
                $this->scheduleStep($enrollment, $firstStep);
            }

            return $enrollment;
        });
    }

    public function scheduleStep(DripEnrollment $enrollment, DripStep $step): ?DripStepRun
    {
        if ($enrollment->status !== 'active') {
            return null;
        }

        $existing = DripStepRun::query()
            ->where('drip_enrollment_id', $enrollment->id)
            ->where('drip_step_id', $step->id)
            ->where('status', 'pending')
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $scheduledAt = now()
            ->addDays($step->delay_days)
            ->addHours($step->delay_hours);

        $run = DripStepRun::query()->create([
            'drip_enrollment_id' => $enrollment->id,
            'drip_step_id' => $step->id,
            'scheduled_at' => $scheduledAt,
            'status' => 'pending',
        ]);

        $enrollment->update(['current_step_id' => $step->id]);

        $queue = $step->channel === 'sms'
            ? config('fil-notifications.queues.notifications', 'notifications')
            : config('fil-notifications.queues.emails', 'emails');

        SendDripStepJob::dispatch($run->id)
            ->delay($scheduledAt)
            ->onQueue($queue);

        return $run;
    }

    public function advanceAfterStep(DripEnrollment $enrollment, DripStep $completedStep): void
    {
        $enrollment->refresh()->load(['campaign', 'lead']);

        if ($enrollment->status !== 'active') {
            return;
        }

        $campaign = $enrollment->campaign;

        if ($campaign === null || $campaign->status !== 'active') {
            return;
        }

        $lead = $enrollment->lead;

        if ($lead !== null && ! $lead->eligible_for_drip) {
            $enrollment->update([
                'status' => 'paused',
                'paused_at' => now(),
            ]);

            return;
        }

        $nextStep = DripStep::query()
            ->where('drip_campaign_id', $campaign->id)
            ->where('status', 'active')
            ->where('sort_order', '>', $completedStep->sort_order)
            ->orderBy('sort_order')
            ->first();

        if ($nextStep === null) {
            $enrollment->update([
                'status' => 'completed',
                'completed_at' => now(),
                'current_step_id' => null,
            ]);

            return;
        }

        $this->scheduleStep($enrollment, $nextStep);
    }

    private function resolveCampaign(Lead $lead, ?string $triggerEvent): ?DripCampaign
    {
        if ($lead->drip_campaign_id !== null) {
            return DripCampaign::query()->find($lead->drip_campaign_id);
        }

        $query = DripCampaign::query()->where('status', 'active');

        if ($triggerEvent !== null) {
            $query->where('trigger_event', $triggerEvent);
        }

        return $query->orderBy('id')->first();
    }
}
