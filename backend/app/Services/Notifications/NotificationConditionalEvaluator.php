<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\Lead;

final class NotificationConditionalEvaluator
{
    public function __construct(
        private readonly NotificationConditionNormalizer $normalizer,
    ) {}

    /**
     * @param  array<string, mixed>|null  $conditionals
     * @param  list<string>  $changedFields
     */
    public function shouldSend(?array $conditionals, Lead $lead, array $changedFields = []): bool
    {
        $normalized = $this->normalizer->normalize($conditionals);

        if ($normalized === null) {
            return true;
        }

        $mode = $normalized['mode'] ?? 'always';
        $groups = $normalized['groups'] ?? [];

        if ($mode === 'always' || $groups === []) {
            return true;
        }

        $matched = false;

        foreach ($groups as $group) {
            if (! is_array($group)) {
                continue;
            }

            $groupMatch = ($group['match'] ?? 'all') === 'any' ? 'any' : 'all';
            $conditions = $group['conditions'] ?? [];

            if (! is_array($conditions) || $conditions === []) {
                continue;
            }

            $groupPass = $groupMatch === 'all';

            foreach ($conditions as $condition) {
                if (! is_array($condition)) {
                    continue;
                }

                $passes = $this->evaluateCondition($condition, $lead, $changedFields);

                if ($groupMatch === 'all' && ! $passes) {
                    $groupPass = false;

                    break;
                }

                if ($groupMatch === 'any' && $passes) {
                    $groupPass = true;

                    break;
                }
            }

            if ($groupPass) {
                $matched = true;

                break;
            }
        }

        return match ($mode) {
            'send_if' => $matched,
            'skip_if' => ! $matched,
            default => true,
        };
    }

    /**
     * @param  array<string, mixed>  $condition
     * @param  list<string>  $changedFields
     */
    private function evaluateCondition(array $condition, Lead $lead, array $changedFields): bool
    {
        $operator = (string) ($condition['op'] ?? 'eq');
        $expected = $condition['value'] ?? '';
        $field = (string) ($condition['field'] ?? '');
        $actual = $this->leadFieldValue($lead, $field);

        if ($operator === 'changed') {
            return $field !== '' && in_array($field, $changedFields, true);
        }

        if ($operator === 'changed_to') {
            return $field !== ''
                && in_array($field, $changedFields, true)
                && $this->compare('eq', $actual, (string) $expected);
        }

        return $this->compare($operator, $actual, (string) $expected);
    }

    private function leadFieldValue(Lead $lead, string $field): string
    {
        if ($field === '') {
            return '';
        }

        $formData = is_array($lead->form_data ?? null) ? $lead->form_data : [];
        $value = $lead->getAttribute($field) ?? $formData[$field] ?? null;

        return is_scalar($value) ? (string) $value : '';
    }

    private function compare(string $operator, string $actual, string $expected): bool
    {
        $actualLower = strtolower(trim($actual));
        $expectedLower = strtolower(trim($expected));

        return match ($operator) {
            'eq' => $actualLower === $expectedLower,
            'neq' => $actualLower !== $expectedLower,
            'empty' => trim($actual) === '',
            'not_empty' => trim($actual) !== '',
            'contains' => str_contains($actualLower, $expectedLower),
            'not_contains' => ! str_contains($actualLower, $expectedLower),
            'lt' => is_numeric($actual) && is_numeric($expected) && (float) $actual < (float) $expected,
            'gt' => is_numeric($actual) && is_numeric($expected) && (float) $actual > (float) $expected,
            'lte' => is_numeric($actual) && is_numeric($expected) && (float) $actual <= (float) $expected,
            'gte' => is_numeric($actual) && is_numeric($expected) && (float) $actual >= (float) $expected,
            'regex' => @preg_match('/'.$expected.'/i', $actual) === 1,
            default => $actualLower === $expectedLower,
        };
    }
}
