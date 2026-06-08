<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Services\Fields\EntityFieldValueReader;
use App\Support\Legacy\LegacyPostTypeEntityMap;
use Illuminate\Database\Eloquent\Model;

final class LegacyImportBaselineCompareService
{
    /** @var array<string, class-string<Model>> */
    private const ENTITY_MODELS = [
        'lead' => Lead::class,
        'store' => Store::class,
        'area' => Area::class,
        'organization' => Organization::class,
        'franchise_location' => FranchiseLocation::class,
    ];

    public function __construct(
        private readonly EntityFieldValueReader $fieldValues,
    ) {}

    /**
     * @return array{ok: bool, diffs: list<string>}
     */
    public function compare(string $goldenPath): array
    {
        if (! is_readable($goldenPath)) {
            throw new \InvalidArgumentException("Golden file not readable: {$goldenPath}");
        }

        /** @var array{version?: int, cases?: list<array<string, mixed>>}|null $golden */
        $golden = json_decode((string) file_get_contents($goldenPath), true);

        if (! is_array($golden) || ! isset($golden['cases']) || ! is_array($golden['cases'])) {
            throw new \InvalidArgumentException('Golden file must contain a cases array.');
        }

        $diffs = [];

        foreach ($golden['cases'] as $index => $case) {
            if (! is_array($case)) {
                $diffs[] = "case {$index}: invalid case payload";

                continue;
            }

            $diffs = array_merge($diffs, $this->compareCase($case, $index));
        }

        return ['ok' => $diffs === [], 'diffs' => $diffs];
    }

    /**
     * @param  array<string, mixed>  $case
     * @return list<string>
     */
    private function compareCase(array $case, int $index): array
    {
        $legacyPostId = (int) ($case['legacy_post_id'] ?? 0);
        $entity = (string) ($case['entity'] ?? '');

        if ($legacyPostId <= 0 || $entity === '') {
            return ["case {$index}: legacy_post_id and entity are required"];
        }

        $modelClass = self::ENTITY_MODELS[$entity] ?? null;

        if ($modelClass === null) {
            return ["case {$index}: unknown entity {$entity}"];
        }

        /** @var Model|null $record */
        $record = $modelClass::query()->where('legacy_post_id', $legacyPostId)->first();

        if ($record === null) {
            return ["case {$index}: no {$entity} with legacy_post_id {$legacyPostId}"];
        }

        $diffs = [];
        $label = "case {$index} ({$entity}#{$legacyPostId})";

        /** @var array<string, mixed> $expectedTier1 */
        $expectedTier1 = is_array($case['tier1'] ?? null) ? $case['tier1'] : [];

        foreach ($expectedTier1 as $column => $expected) {
            $actual = data_get($record, $column);

            if (! $this->valuesMatch($expected, $actual)) {
                $diffs[] = "{$label} tier1.{$column}: expected ".json_encode($expected).', got '.json_encode($actual);
            }
        }

        /** @var array<string, mixed> $expectedFields */
        $expectedFields = is_array($case['field_values'] ?? null) ? $case['field_values'] : [];

        if ($expectedFields !== []) {
            $fieldEntity = $entity === 'franchise_location' ? 'store' : $entity;
            $legacyPostType = is_string($case['legacy_post_type'] ?? null) && $case['legacy_post_type'] !== ''
                ? (string) $case['legacy_post_type']
                : LegacyPostTypeEntityMap::defaultPostTypeForEntity($fieldEntity);

            $actualFields = $this->fieldValues->forEntity(
                $fieldEntity,
                (int) $record->getKey(),
                $legacyPostType,
            );

            foreach ($expectedFields as $fieldKey => $expected) {
                $actual = $actualFields[$fieldKey] ?? null;

                if (! $this->valuesMatch($expected, $actual)) {
                    $diffs[] = "{$label} field_values.{$fieldKey}: expected ".json_encode($expected).', got '.json_encode($actual);
                }
            }
        }

        return $diffs;
    }

    private function valuesMatch(mixed $expected, mixed $actual): bool
    {
        if ($expected === null && ($actual === null || $actual === '')) {
            return true;
        }

        if (is_bool($expected)) {
            return (bool) $actual === $expected;
        }

        if (is_int($expected) || is_float($expected)) {
            return (string) $actual === (string) $expected;
        }

        return (string) $actual === (string) $expected;
    }
}
