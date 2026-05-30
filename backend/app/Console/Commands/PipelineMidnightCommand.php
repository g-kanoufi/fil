<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\Pos\SyncPosRevenueJob;
use App\Models\Lead;
use App\Models\PosConnection;
use App\Services\Drips\DripEnrollmentService;
use App\Services\Leads\LeadPipelineCatalog;
use App\Services\Leads\LeadPipelineService;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

final class PipelineMidnightCommand extends Command
{
    protected $signature = 'pipeline:midnight';

    protected $description = 'Nightly pipeline maintenance — waiting periods, drip eligibility, FDD statuses';

    public function handle(
        LeadPipelineCatalog $catalog,
        LeadPipelineService $pipeline,
        DripEnrollmentService $drips,
        NotificationDispatcher $notifications,
    ): int {
        $this->info('Running pipeline midnight tasks…');

        $waitingPeriodUpdated = $this->syncWaitingPeriodStatuses($catalog, $pipeline, $notifications);
        $dripEligible = $this->syncDripEligibility($drips);
        $posDispatched = $this->dispatchPosSyncJobs();

        $this->info(sprintf(
            'Done. Waiting period updates: %d, drip eligibility: %d, POS jobs: %d',
            $waitingPeriodUpdated,
            $dripEligible,
            $posDispatched,
        ));

        return self::SUCCESS;
    }

    private function syncWaitingPeriodStatuses(
        LeadPipelineCatalog $catalog,
        LeadPipelineService $pipeline,
        NotificationDispatcher $notifications,
    ): int {
        $updated = 0;

        Lead::query()
            ->where('status', 'active')
            ->whereNotNull('waiting_period_ends_at')
            ->chunkById(100, function ($leads) use ($catalog, $pipeline, $notifications, &$updated): void {
                foreach ($leads as $lead) {
                    $endsAt = $lead->waiting_period_ends_at;

                    if ($endsAt === null) {
                        continue;
                    }

                    if ($endsAt->isPast()) {
                        if ($lead->lead_fdd_status !== 'out_of_waiting_period') {
                            $lead->update(['lead_fdd_status' => 'out_of_waiting_period']);
                        }

                        if ((int) $lead->pipeline_phase < 9) {
                            $pipeline->transition($lead->fresh(), 9);
                        }

                        $notifications->dispatch('application.waiting_period_over', $lead->fresh());
                        $updated++;

                        continue;
                    }

                    if ($lead->lead_fdd_status !== 'waiting_period' && $lead->lead_fdd_status !== 'in_waiting_period') {
                        $lead->update(['lead_fdd_status' => 'waiting_period']);
                        $updated++;
                    }

                    $resolved = $catalog->resolvePipelinePhase($lead->fresh());

                    if ($resolved !== (int) $lead->pipeline_phase) {
                        $lead->update(['pipeline_phase' => $resolved]);
                        $updated++;
                    }
                }
            });

        return $updated;
    }

    private function syncDripEligibility(DripEnrollmentService $drips): int
    {
        $count = 0;

        Lead::query()
            ->where('status', 'active')
            ->where('eligible_for_drip', false)
            ->where('pipeline_phase', '<=', 4)
            ->chunkById(100, function ($leads) use ($drips, &$count): void {
                foreach ($leads as $lead) {
                    $lead->update(['eligible_for_drip' => true]);
                    $drips->enroll($lead->fresh(), 'pipeline_midnight');
                    $count++;
                }
            });

        return $count;
    }

    private function dispatchPosSyncJobs(): int
    {
        $connections = PosConnection::query()->where('status', 'active')->pluck('id');
        $count = 0;

        foreach ($connections as $connectionId) {
            SyncPosRevenueJob::dispatch((int) $connectionId)
                ->onQueue('default');
            $count++;
        }

        Log::info('POS sync jobs dispatched from pipeline:midnight', ['count' => $count]);

        return $count;
    }
}
