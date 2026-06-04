<?php

declare(strict_types=1);

namespace App\Support\Fields;

/**
 * Normalized, queryable view of a select field's config.choices.
 *
 * @phpstan-type Choice array{value: string, label: string, aliases?: list<string>, meta?: array<string, mixed>}
 */
final class FieldChoiceSet
{
    /** @var list<Choice> */
    private array $choices;

    /** @var array<string, Choice> */
    private array $index = [];

    /**
     * @param  list<Choice>|array<string, string>|null  $raw
     */
    public function __construct(?array $raw)
    {
        $this->choices = self::normalize($raw);
        $this->buildIndex();
    }

    /**
     * @param  list<Choice>|array<string, string>|null  $raw
     * @return list<Choice>
     */
    public static function normalize(?array $raw): array
    {
        if ($raw === null || $raw === []) {
            return [];
        }

        if (array_is_list($raw) && isset($raw[0]['value'])) {
            /** @var list<Choice> $raw */
            return array_values(array_map(static function (array $choice): array {
                return [
                    'value' => (string) $choice['value'],
                    'label' => (string) ($choice['label'] ?? $choice['value']),
                    'aliases' => isset($choice['aliases']) && is_array($choice['aliases'])
                        ? array_map(strval(...), $choice['aliases'])
                        : [],
                    'meta' => is_array($choice['meta'] ?? null) ? $choice['meta'] : [],
                ];
            }, $raw));
        }

        $normalized = [];

        foreach ($raw as $value => $label) {
            if (is_array($label)) {
                continue;
            }

            $normalized[] = [
                'value' => (string) $value,
                'label' => (string) $label,
                'aliases' => [],
                'meta' => [],
            ];
        }

        return $normalized;
    }

    /**
     * @return list<Choice>
     */
    public function all(): array
    {
        return $this->choices;
    }

    public function isEmpty(): bool
    {
        return $this->choices === [];
    }

    public function labelFor(?string $storedValue): ?string
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return null;
        }

        $choice = $this->find($storedValue);

        return $choice['label'] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function metaFor(?string $storedValue): array
    {
        if ($storedValue === null || trim($storedValue) === '') {
            return [];
        }

        return $this->find($storedValue)['meta'] ?? [];
    }

    public function isClosed(?string $storedValue): bool
    {
        return (bool) ($this->metaFor($storedValue)['closed'] ?? false);
    }

    public function pipelinePhase(?string $storedValue): ?int
    {
        $phase = $this->metaFor($storedValue)['pipeline_phase'] ?? null;

        return is_numeric($phase) ? (int) $phase : null;
    }

    public function categoryFor(?string $storedValue): ?string
    {
        $category = $this->metaFor($storedValue)['category'] ?? null;

        return is_string($category) && $category !== '' ? $category : null;
    }

    /**
     * Stored values and aliases suitable for grid WHERE IN matching.
     *
     * @return list<string>
     */
    public function filterValuesFor(?string $storedValue): array
    {
        $choice = $this->matchChoice($storedValue ?? '');

        if ($choice === null) {
            $trimmed = trim((string) $storedValue);

            return $trimmed === '' ? [] : [$trimmed, strtolower($trimmed)];
        }

        $values = [
            $choice['value'],
            strtolower($choice['value']),
            $choice['label'],
            strtolower($choice['label']),
        ];

        foreach ($choice['aliases'] ?? [] as $alias) {
            $values[] = $alias;
            $values[] = strtolower($alias);
        }

        $slug = strtolower(preg_replace('/[-\s]+/', '_', $choice['value']) ?? $choice['value']);
        $values[] = $slug;

        return array_values(array_unique(array_filter($values, fn (string $v): bool => $v !== '')));
    }

    /**
     * @return Choice|null
     */
    public function matchChoice(string $storedValue): ?array
    {
        return $this->find($storedValue);
    }

    /**
     * @return Choice|null
     */
    private function find(string $storedValue): ?array
    {
        $trimmed = trim($storedValue);
        $lower = strtolower($trimmed);

        if (isset($this->index[$trimmed])) {
            return $this->index[$trimmed];
        }

        if (isset($this->index[$lower])) {
            return $this->index[$lower];
        }

        if (is_numeric($trimmed) && isset($this->index[(string) (int) $trimmed])) {
            return $this->index[(string) (int) $trimmed];
        }

        return null;
    }

    private function buildIndex(): void
    {
        foreach ($this->choices as $choice) {
            $this->index[$choice['value']] = $choice;
            $this->index[strtolower($choice['value'])] = $choice;

            foreach ($choice['aliases'] ?? [] as $alias) {
                $this->index[$alias] = $choice;
                $this->index[strtolower($alias)] = $choice;
            }

            $this->index[strtolower($choice['label'])] = $choice;
        }
    }

    /**
     * Merge metadata from defaults onto existing choices matched by value, alias, or label.
     *
     * @param  list<Choice>  $defaults
     * @return list<Choice>
     */
    public function enrichFromDefaults(array $defaults): array
    {
        if ($this->isEmpty()) {
            return $defaults;
        }

        $defaultSet = new self($defaults);
        $enriched = [];

        foreach ($this->choices as $choice) {
            $match = $defaultSet->matchChoice($choice['value'])
                ?? $defaultSet->matchChoice($choice['label']);

            $meta = $choice['meta'] ?? [];

            if ($match !== null) {
                $meta = array_merge($match['meta'] ?? [], $meta);
            }

            $enriched[] = [
                'value' => $choice['value'],
                'label' => $choice['label'],
                'aliases' => array_values(array_unique(array_merge(
                    $choice['aliases'] ?? [],
                    $match['aliases'] ?? [],
                ))),
                'meta' => $meta,
            ];
        }

        return $enriched;
    }
}
