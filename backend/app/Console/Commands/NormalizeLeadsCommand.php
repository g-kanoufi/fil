<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Services\Leads\LeadPipelineCatalog;
use Illuminate\Console\Command;

final class NormalizeLeadsCommand extends Command
{
    protected $signature = 'fil:normalize-leads {--dry-run : Preview changes without writing}';

    protected $description = 'Normalize lead pipeline phases and status presentation from legacy import data';

    public function handle(LeadPipelineCatalog $catalog): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $unchanged = 0;

        Lead::query()->orderBy('id')->chunkById(100, function ($leads) use ($catalog, $dryRun, &$updated, &$unchanged): void {
            foreach ($leads as $lead) {
                $resolvedPhase = $catalog->resolvePipelinePhase($lead);

                if ($resolvedPhase === (int) $lead->pipeline_phase) {
                    $unchanged++;

                    continue;
                }

                $updated++;

                if ($dryRun) {
                    $this->line(sprintf(
                        'Lead #%d (%s): phase %d → %d (%s)',
                        $lead->id,
                        $lead->title,
                        $lead->pipeline_phase,
                        $resolvedPhase,
                        $catalog->phaseLabel($resolvedPhase),
                    ));

                    continue;
                }

                $lead->update(['pipeline_phase' => $resolvedPhase]);
            }
        });

        $this->info(sprintf(
            '%s %d lead(s); %d unchanged.',
            $dryRun ? 'Would update' : 'Updated',
            $updated,
            $unchanged,
        ));

        return self::SUCCESS;
    }
}
