<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Services\Fields\RepeaterValueWriter;
use App\Support\Fields\FieldTypes;
use Illuminate\Support\Collection;

/**
 * Promotes legacy repeater row meta into normalized repeater tables.
 */
final class LegacyRepeaterMetaAggregator
{
    public function __construct(
        private readonly RepeaterValueWriter $repeaterValues,
    ) {}

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $repeaterRows
     * @param  Collection<string, Field>  $fieldIndex
     */
    public function writeRows(
        string $entityType,
        int $entityId,
        array $repeaterRows,
        Collection $fieldIndex,
    ): int {
        $written = 0;

        foreach ($repeaterRows as $fieldKey => $rows) {
            $field = $fieldIndex->get($fieldKey);

            if (! $field instanceof Field || ! FieldTypes::isRepeater($field->type)) {
                continue;
            }

            ksort($rows);
            $this->repeaterValues->write($entityType, $entityId, $field, array_values($rows));
            $written++;
        }

        return $written;
    }

    /**
     * @param  array<string, array<int, array<string, mixed>>>  $repeaterRows
     * @return array<string, string> JSON payloads for legacy non-repeater parent fields
     */
    public function jsonFallbackPayloads(array $repeaterRows, Collection $fieldIndex): array
    {
        $payloads = [];

        foreach ($repeaterRows as $fieldKey => $rows) {
            $field = $fieldIndex->get($fieldKey);

            if ($field instanceof Field && FieldTypes::isRepeater($field->type)) {
                continue;
            }

            ksort($rows);
            $payloads[$fieldKey] = json_encode(array_values($rows), JSON_THROW_ON_ERROR);
        }

        return $payloads;
    }
}
