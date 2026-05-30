<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Converts legacy imported conditionals and FIL v2 schema into one canonical shape.
 *
 * Canonical:
 * {
 *   "v": 2,
 *   "mode": "send_if"|"skip_if"|"always",
 *   "groups": [{ "match": "all"|"any", "conditions": [{ "field", "op", "value" }] }]
 * }
 */
final class NotificationConditionNormalizer
{
    /**
     * @param  array<string, mixed>|null  $conditionals
     * @return array<string, mixed>|null
     */
    public function normalize(?array $conditionals): ?array
    {
        if ($conditionals === null || $conditionals === []) {
            return null;
        }

        if (($conditionals['v'] ?? null) === 2) {
            return $this->sanitizeV2($conditionals);
        }

        return $this->fromLegacy($conditionals);
    }

    /**
     * @param  array<string, mixed>  $conditionals
     * @return array<string, mixed>|null
     */
    private function fromLegacy(array $conditionals): ?array
    {
        $legacyMode = strtolower((string) ($conditionals['rule'] ?? 'off'));
        $mode = match ($legacyMode) {
            'do', 'and' => 'send_if',
            'dont' => 'skip_if',
            'off' => 'always',
            default => 'always',
        };

        $legacyGroups = $conditionals['groups'] ?? [];

        if ($mode === 'always' || ! is_array($legacyGroups) || $legacyGroups === []) {
            return ['v' => 2, 'mode' => 'always', 'groups' => []];
        }

        $groups = [];

        foreach ($legacyGroups as $legacyGroup) {
            if (! is_array($legacyGroup) || $legacyGroup === []) {
                continue;
            }

            $conditions = [];

            foreach ($legacyGroup as $rule) {
                if (! is_array($rule)) {
                    continue;
                }

                $field = $this->resolveField(
                    (string) ($rule['field'] ?? ''),
                    (string) ($rule['merge_tag'] ?? $rule['merge_tag_select'] ?? ''),
                );

                if ($field === null) {
                    continue;
                }

                $conditions[] = [
                    'field' => $field,
                    'op' => $this->mapOperator((string) ($rule['operator'] ?? 'equal')),
                    'value' => $rule['value'] ?? '',
                ];
            }

            if ($conditions !== []) {
                $groups[] = ['match' => 'all', 'conditions' => $conditions];
            }
        }

        if ($groups === []) {
            return ['v' => 2, 'mode' => 'always', 'groups' => []];
        }

        return ['v' => 2, 'mode' => $mode, 'groups' => $groups];
    }

    /**
     * @param  array<string, mixed>  $conditionals
     * @return array<string, mixed>
     */
    private function sanitizeV2(array $conditionals): array
    {
        $mode = in_array($conditionals['mode'] ?? '', ['send_if', 'skip_if', 'always'], true)
            ? $conditionals['mode']
            : 'always';

        $groups = [];

        foreach ($conditionals['groups'] ?? [] as $group) {
            if (! is_array($group)) {
                continue;
            }

            $match = ($group['match'] ?? 'all') === 'any' ? 'any' : 'all';
            $conditions = [];

            foreach ($group['conditions'] ?? [] as $condition) {
                if (! is_array($condition)) {
                    continue;
                }

                $field = $this->resolveField(
                    (string) ($condition['field'] ?? ''),
                    '',
                );

                if ($field === null) {
                    continue;
                }

                $conditions[] = [
                    'field' => $field,
                    'op' => $this->mapOperator((string) ($condition['op'] ?? 'eq')),
                    'value' => $condition['value'] ?? '',
                ];
            }

            if ($conditions !== []) {
                $groups[] = ['match' => $match, 'conditions' => $conditions];
            }
        }

        return ['v' => 2, 'mode' => $mode, 'groups' => $groups];
    }

    private function resolveField(string $field, string $mergeTag): ?string
    {
        if ($field !== '') {
            if (str_starts_with($field, 'lead.')) {
                $field = substr($field, 5);
            }

            return $this->mapFieldName($field);
        }

        if (preg_match('/\{postmeta\/([a-z0-9_\-]+)\}/i', $mergeTag, $matches)) {
            return $this->mapFieldName($matches[1]);
        }

        if (preg_match('/postmeta\/([a-z0-9_\-]+)/i', $mergeTag, $matches)) {
            return $this->mapFieldName($matches[1]);
        }

        return null;
    }

    private function mapFieldName(string $field): string
    {
        /** @var array<string, string> $map */
        $map = config('fil-notifications.field_map', []);

        return $map[$field] ?? $field;
    }

    private function mapOperator(string $operator): string
    {
        return match (strtolower($operator)) {
            'equal', 'is', 'eq' => 'eq',
            'not_equal', 'is_not', 'neq' => 'neq',
            'empty', 'is_empty' => 'empty',
            'not_empty', 'is_not_empty' => 'not_empty',
            'contains' => 'contains',
            'not_contains' => 'not_contains',
            'lt', 'less_than' => 'lt',
            'gt', 'greater_than' => 'gt',
            'lte' => 'lte',
            'gte' => 'gte',
            'regex' => 'regex',
            'is_updated', 'changed' => 'changed',
            'is_updated_to', 'changed_to' => 'changed_to',
            default => 'eq',
        };
    }
}
