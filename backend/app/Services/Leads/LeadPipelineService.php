<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Events\Leads\LeadPhaseChanged;
use App\Models\Lead;
use App\Models\LeadPhaseEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final class LeadPipelineService
{
    public function __construct(
        private readonly LeadPipelineCatalog $catalog,
    ) {}

    public function transition(Lead $lead, int $toPhase, ?User $actor = null, array $meta = []): Lead
    {
        if (! in_array($toPhase, $this->catalog->validPhases(), true)) {
            throw new InvalidArgumentException("Invalid pipeline phase: {$toPhase}");
        }

        $fromPhase = (int) $lead->pipeline_phase;

        if ($fromPhase === $toPhase) {
            return $lead;
        }

        return DB::transaction(function () use ($lead, $fromPhase, $toPhase, $actor, $meta): Lead {
            $lead->update(['pipeline_phase' => $toPhase]);

            LeadPhaseEvent::query()->create([
                'lead_id' => $lead->id,
                'from_phase' => $fromPhase,
                'to_phase' => $toPhase,
                'actor_user_id' => $actor?->id,
                'meta' => $meta === [] ? null : $meta,
                'created_at' => now(),
            ]);

            LeadPhaseChanged::dispatch($lead->fresh(), $fromPhase, $toPhase, $actor);

            return $lead->fresh();
        });
    }
}
