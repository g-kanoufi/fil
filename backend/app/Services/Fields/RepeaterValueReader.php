<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\Field;
use App\Models\FieldRepeaterRow;
use App\Models\FieldRepeaterValue;
use App\Support\Fields\FieldTypes;

final class RepeaterValueReader
{
    /**
     * @return list<array<string, mixed>>
     */
    public function read(string $entityType, int $entityId, Field $parentField): array
    {
        if (! FieldTypes::isRepeater($parentField->type)) {
            return [];
        }

        $rows = FieldRepeaterRow::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->where('field_id', $parentField->id)
            ->with(['values.subField'])
            ->orderBy('sort_order')
            ->get();

        $result = [];

        foreach ($rows as $row) {
            $entry = [];

            foreach ($row->values as $value) {
                $subField = $value->subField;

                if ($subField === null) {
                    continue;
                }

                $entry[$subField->key] = $this->extractScalar($subField->type, $value);
            }

            $result[] = $entry;
        }

        return $result;
    }

    private function extractScalar(string $type, FieldRepeaterValue $row): mixed
    {
        return match ($type) {
            FieldTypes::NUMBER, FieldTypes::RANGE => $row->value_number,
            FieldTypes::TRUE_FALSE => $row->value_boolean,
            FieldTypes::DATE => $row->value_date?->format('Y-m-d'),
            FieldTypes::DATE_TIME => $row->value_datetime?->toIso8601String(),
            FieldTypes::MULTISELECT => $row->value_json ?? [],
            default => $row->value_text,
        };
    }
}
