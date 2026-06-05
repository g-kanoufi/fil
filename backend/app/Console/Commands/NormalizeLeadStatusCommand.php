<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Lead;
use App\Support\Fields\FieldChoiceCatalog;
use App\Support\Fields\FieldChoiceSet;
use Illuminate\Console\Command;

final class NormalizeLeadStatusCommand extends Command
{
    protected $signature = 'leads:normalize-status {--dry-run : Report changes without writing}';

    protected $description = 'Map lead_fdd_status into canonical lead_status and backfill pipeline_phase from field catalog meta.';

    public function handle(FieldChoiceCatalog $choices): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $statusSet = $choices->choicesFor('lead', 'lead_status');
        $fddSet = $choices->choicesFor('lead', 'lead_fdd_status');

        $updated = 0;
        $unmapped = [];

        Lead::query()->orderBy('id')->chunkById(200, function ($leads) use ($statusSet, $fddSet, $dryRun, &$updated, &$unmapped): void {
            foreach ($leads as $lead) {
                $changes = [];
                $canonical = $this->canonicalValue($statusSet, $fddSet, $lead->lead_status)
                    ?? $this->canonicalValue($statusSet, $fddSet, $lead->lead_fdd_status);

                if ($canonical !== null) {
                    if ($lead->lead_status !== $canonical) {
                        $changes['lead_status'] = $canonical;
                    }

                    if ($lead->lead_fdd_status !== $canonical) {
                        $changes['lead_fdd_status'] = $canonical;
                    }

                    $phase = $statusSet->pipelinePhase($canonical) ?? $fddSet->pipelinePhase($canonical);
                    if ($phase !== null && (int) $lead->pipeline_phase < 2) {
                        $changes['pipeline_phase'] = $phase;
                    }
                } elseif (trim((string) $lead->lead_fdd_status) !== '' || trim((string) $lead->lead_status) !== '') {
                    $unmapped[$lead->id] = [
                        'lead_status' => $lead->lead_status,
                        'lead_fdd_status' => $lead->lead_fdd_status,
                    ];
                }

                if ($changes === []) {
                    continue;
                }

                $updated++;

                if ($dryRun) {
                    $this->line("Lead {$lead->id}: ".json_encode($changes));

                    continue;
                }

                $lead->update($changes);
            }
        });

        $this->info($dryRun ? "Would update {$updated} lead(s)." : "Updated {$updated} lead(s).");

        if ($unmapped !== []) {
            $this->warn('Unmapped status values on '.count($unmapped).' lead(s) — add choices in Fields admin or fix data.');
            foreach (array_slice($unmapped, 0, 10, true) as $id => $row) {
                $this->line("  #{$id}: lead_status={$row['lead_status']} lead_fdd_status={$row['lead_fdd_status']}");
            }
        }

        return self::SUCCESS;
    }

    private function canonicalValue(FieldChoiceSet $primary, FieldChoiceSet $fdd, ?string $stored): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $match = $primary->matchChoice($stored) ?? $fdd->matchChoice($stored);

        return $match['value'] ?? null;
    }
}
