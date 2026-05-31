<?php

declare(strict_types=1);

namespace App\Support\Fields;

use App\Models\Field;

/**
 * Reads select labels and semantic metadata from the fields schema (DB).
 * Falls back to legacy config only when schema has no matching choice.
 */
final class FieldChoiceCatalog
{
    /** @var array<string, FieldChoiceSet> */
    private array $cache = [];

    public function choicesFor(string $entity, string $fieldKey): FieldChoiceSet
    {
        $cacheKey = "{$entity}.{$fieldKey}";

        if (! isset($this->cache[$cacheKey])) {
            $field = Field::query()
                ->where('entity', $entity)
                ->where('key', $fieldKey)
                ->where('status', 'active')
                ->first();

            $raw = is_array($field?->config) ? ($field->config['choices'] ?? null) : null;
            $this->cache[$cacheKey] = new FieldChoiceSet(is_array($raw) ? $raw : null);
        }

        return $this->cache[$cacheKey];
    }

    public function label(string $entity, string $fieldKey, ?string $storedValue): ?string
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return null;
        }

        $label = $this->choicesFor($entity, $fieldKey)->labelFor($storedValue);

        if ($label !== null) {
            return $label;
        }

        return $this->legacyLabel($fieldKey, $storedValue);
    }

    public function isClosed(string $entity, string $fieldKey, ?string $storedValue): bool
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return false;
        }

        $set = $this->choicesFor($entity, $fieldKey);

        if (! $set->isEmpty()) {
            return $set->isClosed($storedValue);
        }

        return $this->legacyIsClosed($storedValue);
    }

    public function pipelinePhaseHint(string $entity, string $fieldKey, ?string $storedValue): ?int
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return null;
        }

        $set = $this->choicesFor($entity, $fieldKey);

        if (! $set->isEmpty()) {
            $phase = $set->pipelinePhase($storedValue);

            if ($phase !== null) {
                return $phase;
            }
        }

        return $this->legacyPhaseHint($storedValue);
    }

    public function isSystemField(string $entity, string $fieldKey): bool
    {
        /** @var array<string, array<string, mixed>> $keys */
        $keys = config('fil-fields.system_keys', []);

        return isset($keys[$entity][$fieldKey]);
    }

    private function legacyLabel(string $fieldKey, string $value): ?string
    {
        $trimmed = trim($value);
        $lower = strtolower($trimmed);

        if ($fieldKey === 'lead_status') {
            /** @var array<string, string> $numeric */
            $numeric = config('fil-pipeline.lead_status_labels', []);

            if (isset($numeric[$trimmed])) {
                return $numeric[$trimmed];
            }

            if (is_numeric($trimmed) && isset($numeric[(string) (int) $trimmed])) {
                return $numeric[(string) (int) $trimmed];
            }
        }

        if ($fieldKey === 'lead_fdd_status') {
            /** @var array<string, string> $fdd */
            $fdd = config('fil-pipeline.fdd_status_labels', []);

            if (isset($fdd[$lower])) {
                return $fdd[$lower];
            }
        }

        return null;
    }

    private function legacyIsClosed(string $value): bool
    {
        $key = strtolower(trim($value));
        /** @var list<string> $closed */
        $closed = config('fil-pipeline.closed_status_keys', []);

        return in_array($key, $closed, true)
            || in_array((string) (int) $value, $closed, true);
    }

    private function legacyPhaseHint(string $value): ?int
    {
        $key = strtolower(trim($value));
        /** @var array<string, int> $hints */
        $hints = config('fil-pipeline.status_phase_hints', []);

        if (isset($hints[$key])) {
            return $hints[$key];
        }

        $label = $this->legacyLabel('lead_fdd_status', $value) ?? $this->legacyLabel('lead_status', $value);

        if ($label !== null) {
            $slug = strtolower($label);

            if (isset($hints[$slug])) {
                return $hints[$slug];
            }

            if (str_contains($slug, 'award')) {
                return 10;
            }

            if (str_contains($slug, 'waiting period') && ! str_contains($slug, 'out of')) {
                return 8;
            }

            if (str_contains($slug, 'out of waiting')) {
                return 9;
            }

            if (str_contains($slug, 'viewed fdd') || str_contains($slug, 'viewed intro')) {
                return 6;
            }

            if (str_contains($slug, 'fdd sent') || str_contains($slug, 'sent fdd') || str_contains($slug, 'sent prior')) {
                return 5;
            }
        }

        return null;
    }
}
