<?php

declare(strict_types=1);

namespace App\Services\Portal;

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\Lead;
use App\Models\User;
use App\Services\Fields\EntityFieldValueReader;
use App\Services\Fields\FieldValueWriter;
use App\Services\Leads\LeadPipelineCatalog;
use App\Services\Leads\LeadPipelineService;
use Illuminate\Support\Collection;

final class ProspectApplicationService
{
    public function __construct(
        private readonly EntityFieldValueReader $fieldReader,
        private readonly FieldValueWriter $fieldWriter,
        private readonly LeadPipelineService $pipeline,
        private readonly LeadPipelineCatalog $pipelineCatalog,
    ) {}

    public function activeLead(User $prospect): Lead
    {
        $lead = Lead::query()
            ->where('prospect_user_id', $prospect->id)
            ->where('pipeline_phase', '!=', 99)
            ->orderByDesc('updated_at')
            ->first();

        if ($lead === null) {
            abort(404, 'No active application found.');
        }

        return $lead;
    }

    /**
     * @return array{
     *   lead: array{id: int, title: string, pipeline_phase: int, pipeline_phase_label: string},
     *   steps: list<array{step: int, label: string, fields: list<array<string, mixed>>}>,
     *   values: array<string, mixed>
     * }
     */
    public function snapshot(User $prospect): array
    {
        $lead = $this->activeLead($prospect);
        $fields = $this->portalFields();
        $values = $this->fieldReader->forEntity('lead', $lead->id, 'application');

        return [
            'lead' => [
                'id' => $lead->id,
                'title' => $lead->title,
                'pipeline_phase' => (int) $lead->pipeline_phase,
                'pipeline_phase_label' => $this->pipelineCatalog->phaseLabel((int) $lead->pipeline_phase),
            ],
            'steps' => $this->buildSteps($fields),
            'values' => $values,
        ];
    }

    /**
     * @param  array<string, mixed>  $values
     */
    public function update(User $prospect, array $values): Lead
    {
        $lead = $this->activeLead($prospect);
        $allowedKeys = $this->portalFields()->pluck('key')->all();
        $filtered = array_intersect_key($values, array_flip($allowedKeys));

        if ($filtered !== []) {
            $this->fieldWriter->write('lead', $lead->id, $filtered);
        }

        $lead = $lead->fresh() ?? $lead;

        if ($this->shouldMarkLongFormComplete($filtered)) {
            $lead->update(['form_data' => array_merge($lead->form_data ?? [], ['long_form_submitted_at' => now()->toIso8601String()])]);

            if ((int) $lead->pipeline_phase < 4) {
                $lead = $this->pipeline->transition($lead, 4, $prospect, ['source' => 'prospect_portal']);
            }
        }

        return $lead->fresh(['prospect']) ?? $lead;
    }

    /**
     * @return Collection<int, Field>
     */
    private function portalFields(): Collection
    {
        /** @var list<string> $groupKeys */
        $groupKeys = config('fil-platform.portal.field_group_keys', ['applications']);
        /** @var list<string> $exclude */
        $exclude = config('fil-platform.portal.exclude_field_keys', []);

        $groupIds = FieldGroup::query()
            ->whereIn('key', $groupKeys)
            ->pluck('id');

        return Field::query()
            ->where('entity', 'lead')
            ->where('status', 'active')
            ->whereIn('field_group_id', $groupIds)
            ->whereNotIn('key', $exclude)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  Collection<int, Field>  $fields
     * @return list<array{step: int, label: string, fields: list<array<string, mixed>>}>
     */
    private function buildSteps(Collection $fields): array
    {
        $grouped = $fields->groupBy(function (Field $field): int {
            $step = $field->config['portal_step'] ?? 1;

            return is_numeric($step) ? (int) $step : 1;
        })->sortKeys();

        $steps = [];

        foreach ($grouped as $stepNumber => $stepFields) {
            $steps[] = [
                'step' => (int) $stepNumber,
                'label' => $stepNumber === 1 ? 'Application' : "Step {$stepNumber}",
                'fields' => $stepFields->map(fn (Field $field): array => [
                    'key' => $field->key,
                    'name' => $field->name,
                    'type' => $field->type,
                    'required' => $field->required,
                    'choices' => $field->config['choices'] ?? null,
                ])->values()->all(),
            ];
        }

        return $steps;
    }

    /**
     * @param  array<string, mixed>  $submitted
     */
    private function shouldMarkLongFormComplete(array $submitted): bool
    {
        $completionKey = (string) config('fil-platform.portal.completion_field_key', 'long_form_complete_date');

        if (array_key_exists($completionKey, $submitted) && filled($submitted[$completionKey])) {
            return true;
        }

        $requiredKeys = $this->portalFields()
            ->where('required', true)
            ->pluck('key')
            ->all();

        if ($requiredKeys === []) {
            return false;
        }

        foreach ($requiredKeys as $key) {
            if (! array_key_exists($key, $submitted) || ! filled($submitted[$key])) {
                return false;
            }
        }

        return true;
    }
}
