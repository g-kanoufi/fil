<?php

declare(strict_types=1);

namespace App\Services\Leads;

use App\Models\Lead;
use App\Support\Fields\FieldChoiceCatalog;

final class LeadApplicationStatusService
{
    public function __construct(
        private readonly FieldChoiceCatalog $choices,
    ) {}

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, mixed>
     */
    public function applyBeforeSave(Lead $lead, array $attributes): array
    {
        if (array_key_exists('lead_status', $attributes)) {
            $incoming = $attributes['lead_status'] !== null ? trim((string) $attributes['lead_status']) : '';
            if ($incoming !== '') {
                $canonical = $this->canonicalValue($incoming) ?? $incoming;
                $attributes['lead_status'] = $canonical;
                $attributes['lead_fdd_status'] = $canonical;
            }
        } elseif (array_key_exists('lead_fdd_status', $attributes)) {
            $incoming = $attributes['lead_fdd_status'] !== null ? trim((string) $attributes['lead_fdd_status']) : '';
            if ($incoming !== '') {
                $canonical = $this->canonicalValue($incoming) ?? $incoming;
                $attributes['lead_status'] = $canonical;
                $attributes['lead_fdd_status'] = $canonical;
            }
        }

        $effective = $attributes['lead_status'] ?? $attributes['lead_fdd_status'] ?? $lead->lead_status ?? $lead->lead_fdd_status;
        $phaseHint = $this->phaseHintFor($effective);

        if ($phaseHint !== null) {
            $current = (int) ($attributes['pipeline_phase'] ?? $lead->pipeline_phase);
            if ($this->shouldAdvancePhase($current, $phaseHint)) {
                $attributes['pipeline_phase'] = $phaseHint;
            }
        }

        return $attributes;
    }

    public function isWonStatus(?string $stored): bool
    {
        if ($stored === null || trim($stored) === '') {
            return false;
        }

        $canonical = $this->canonicalValue($stored);

        if ($canonical !== null) {
            return $this->choices->choicesFor('lead', 'lead_status')->categoryFor($canonical) === 'won'
                || in_array($canonical, ['awarded_franchise', 'awarded_area'], true);
        }

        return str_contains(strtolower($stored), 'award');
    }

    private function canonicalValue(?string $stored): ?string
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        $trimmed = trim($stored);
        $match = $this->choices->choicesFor('lead', 'lead_status')->matchChoice($trimmed)
            ?? $this->choices->choicesFor('lead', 'lead_fdd_status')->matchChoice($trimmed);

        return $match['value'] ?? null;
    }

    private function phaseHintFor(?string $stored): ?int
    {
        if ($stored === null || trim($stored) === '') {
            return null;
        }

        return $this->choices->pipelinePhaseHint('lead', 'lead_status', $stored)
            ?? $this->choices->pipelinePhaseHint('lead', 'lead_fdd_status', $stored);
    }

    private function shouldAdvancePhase(int $current, int $hint): bool
    {
        if ($current === 99) {
            return false;
        }

        if ($hint === 99) {
            return true;
        }

        return $hint > $current;
    }
}
