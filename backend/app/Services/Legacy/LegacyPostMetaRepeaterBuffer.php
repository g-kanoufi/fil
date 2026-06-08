<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Support\Fields\FieldTypes;
use Illuminate\Support\Collection;

/**
 * Buffers repeater row meta during postmeta import and flushes to normalized tables.
 */
final class LegacyPostMetaRepeaterBuffer
{
    /** @var array<int, array{entityType: string, legacyPostType: string, repeaterRows: array<string, array<int, array<string, mixed>>>}> */
    private array $buffers = [];

    public function __construct(
        private readonly LegacyExtrasKeyResolver $keyResolver,
        private readonly LegacyRepeaterMetaAggregator $repeaterMeta,
        private readonly LegacyEntityRecordResolver $entityRecords,
    ) {}

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    public function absorb(
        int $legacyPostId,
        string $entityType,
        string $legacyPostType,
        string $metaKey,
        mixed $metaValue,
        Collection $fieldIndex,
    ): bool {
        $resolved = $this->keyResolver->resolve($metaKey, $fieldIndex, $entityType);

        if ($resolved?->isRepeaterRow() !== true) {
            if ($resolved?->isNestedRepeaterRow() === true) {
                return true;
            }

            if ($this->isRepeaterRowCount($metaKey, $metaValue, $fieldIndex)) {
                return true;
            }

            return false;
        }

        $this->buffers[$legacyPostId] ??= [
            'entityType' => $entityType,
            'legacyPostType' => $legacyPostType,
            'repeaterRows' => [],
        ];

        $this->buffers[$legacyPostId]['repeaterRows'][$resolved->fieldKey][$resolved->rowIndex][$resolved->subKey] = $metaValue;

        return true;
    }

    /**
     * @return array{written: int, skipped: int}
     */
    public function flush(bool $execute): array
    {
        $written = 0;
        $skipped = 0;

        foreach ($this->buffers as $legacyPostId => $payload) {
            $entityType = $payload['entityType'];
            $legacyPostType = $payload['legacyPostType'];
            $entityId = $this->entityRecords->resolveRecordId($entityType, $legacyPostId, $legacyPostType);

            if ($entityId === null) {
                $skipped += count($payload['repeaterRows'], COUNT_RECURSIVE);

                continue;
            }

            $fieldIndex = Field::query()
                ->where('entity', $entityType)
                ->where('status', 'active')
                ->where(function ($query) use ($legacyPostType): void {
                    $query->where('legacy_post_type', '')
                        ->orWhere('legacy_post_type', $legacyPostType);
                })
                ->get()
                ->keyBy('key');

            if ($execute) {
                $written += $this->repeaterMeta->writeRows(
                    $entityType,
                    $entityId,
                    $payload['repeaterRows'],
                    $fieldIndex,
                );
            } else {
                $written += count($payload['repeaterRows']);
            }
        }

        $this->buffers = [];

        return ['written' => $written, 'skipped' => $skipped];
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    private function isRepeaterRowCount(string $metaKey, mixed $value, Collection $fieldIndex): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $field = $fieldIndex->get($metaKey);

        if (! $field instanceof Field) {
            return false;
        }

        /** @var array<string, mixed>|null $config */
        $config = $field->config;

        return ($config['legacy_acf_type'] ?? null) === 'repeater'
            || FieldTypes::isRepeater((string) $field->type);
    }
}
