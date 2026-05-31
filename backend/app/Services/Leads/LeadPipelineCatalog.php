<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\Lead;
use App\Support\Fields\FieldChoiceCatalog;
use Illuminate\Support\Str;

final class LeadPipelineCatalog
{
    public function __construct(
        private readonly FieldChoiceCatalog $choices,
    ) {}

    /**
     * @return array<int, array{label: string, description: string}>
     */
    public function phases(): array
    {
        /** @var array<int, array{label: string, description: string}> */
        return config('fil-pipeline.phases', []);
    }

    /**
     * @return list<int>
     */
    public function validPhases(): array
    {
        /** @var list<int> */
        return config('fil-pipeline.valid_phases', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 99]);
    }

    public function phaseLabel(int $phase): string
    {
        return $this->phases()[$phase]['label'] ?? "Phase {$phase}";
    }

    public function normalizeLegacyProgress(int|string|null $legacyProgress): int
    {
        if ($legacyProgress === null || $legacyProgress === '') {
            return 1;
        }

        $key = (int) $legacyProgress;
        /** @var array<int, int> $map */
        $map = config('fil-pipeline.legacy_progress_map', []);

        return $map[$key] ?? min(max($key, 1), 10);
    }

    public function applicationStatusLabel(Lead $lead): string
    {
        $label = $this->resolveStatusLabel($lead->lead_fdd_status)
            ?? $this->resolveStatusLabel($lead->lead_status);

        if ($label !== null) {
            return $label;
        }

        if ($lead->lead_fdd_status === null && $lead->lead_status === null) {
            return 'New Lead';
        }

        return 'Unknown';
    }

    public function inferPipelinePhase(Lead $lead): int
    {
        $current = (int) $lead->pipeline_phase;

        if ($current > 1 && $current !== 99) {
            return $this->isValidPhase($current) ? $current : 1;
        }

        return $this->inferPhaseFromSignals($lead) ?? max(1, min($current, 10));
    }

    public function resolvePipelinePhase(Lead $lead): int
    {
        $extras = $lead->extras ?? [];

        if (isset($extras['lead_progress']) && $extras['lead_progress'] !== '' && $extras['lead_progress'] !== null) {
            return $this->normalizeLegacyProgress($extras['lead_progress']);
        }

        $inferred = $this->inferPhaseFromSignals($lead);

        if ($inferred !== null) {
            return $inferred;
        }

        $current = (int) $lead->pipeline_phase;

        return $this->isValidPhase($current) ? $current : 1;
    }

    private function inferPhaseFromSignals(Lead $lead): ?int
    {
        foreach ([$lead->lead_fdd_status, $lead->lead_status] as $status) {
            $phase = $this->phaseHintFromStatus($status);

            if ($phase !== null) {
                return $phase;
            }
        }

        if ($lead->fdd_signed_at !== null) {
            return 7;
        }

        if ($lead->disclosed_at !== null) {
            return 5;
        }

        if ($this->isClosedStatus($lead->lead_fdd_status) || $this->isClosedStatus($lead->lead_status)) {
            return 99;
        }

        return null;
    }

    /**
     * @return array{pipeline_phase: int, pipeline_phase_label: string, application_status: string, application_status_label: string}
     */
    public function presentation(Lead $lead): array
    {
        $phase = $this->isValidPhase((int) $lead->pipeline_phase)
            ? (int) $lead->pipeline_phase
            : $this->inferPipelinePhase($lead);

        $statusLabel = $this->applicationStatusLabel($lead);

        return [
            'pipeline_phase' => $phase,
            'pipeline_phase_label' => $this->phaseLabel($phase),
            'application_status' => Str::slug($statusLabel),
            'application_status_label' => $statusLabel,
        ];
    }

    public function normalizeStatusValue(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        return $this->resolveStatusLabel($trimmed) ?? $trimmed;
    }

    public function normalizeAggregationKey(string|int|null $key): string
    {
        if ($key === null || $key === '') {
            return 'Unknown';
        }

        return $this->resolveStatusLabel((string) $key) ?? (string) $key;
    }

    private function resolveStatusLabel(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $trimmed = trim($value);

        return $this->choices->label('lead', 'lead_fdd_status', $trimmed)
            ?? $this->choices->label('lead', 'lead_status', $trimmed)
            ?? $this->humanizeUnknownLabel($trimmed);
    }

    private function humanizeUnknownLabel(string $value): ?string
    {
        $lower = strtolower($value);

        if ($lower === 'unknown' || $lower === 'null') {
            return null;
        }

        return ucwords(str_replace(['_', '-'], ' ', $value));
    }

    private function phaseHintFromStatus(?string $status): ?int
    {
        if ($status === null || trim($status) === '') {
            return null;
        }

        $trimmed = trim($status);

        return $this->choices->pipelinePhaseHint('lead', 'lead_fdd_status', $trimmed)
            ?? $this->choices->pipelinePhaseHint('lead', 'lead_status', $trimmed);
    }

    private function isClosedStatus(?string $status): bool
    {
        if ($status === null || trim($status) === '') {
            return false;
        }

        $trimmed = trim($status);

        return $this->choices->isClosed('lead', 'lead_fdd_status', $trimmed)
            || $this->choices->isClosed('lead', 'lead_status', $trimmed);
    }

    private function isValidPhase(int $phase): bool
    {
        return in_array($phase, $this->validPhases(), true);
    }
}
