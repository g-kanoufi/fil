<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\Field;
use App\Models\FieldRepeaterRow;
use App\Models\FieldRepeaterValue;
use App\Support\Fields\FieldTypes;
use App\Support\Fields\FieldValueCoercion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class RepeaterValueWriter
{
    /**
     * Replace all rows for a repeater field on an entity.
     *
     * @param  list<array<string, mixed>>  $rows  ordered list of sub-field maps
     */
    public function write(string $entityType, int $entityId, Field $parentField, array $rows): void
    {
        if (! FieldTypes::isRepeater($parentField->type)) {
            return;
        }

        $subFields = $this->subFieldIndex($parentField);

        if ($subFields->isEmpty()) {
            return;
        }

        DB::transaction(function () use ($entityType, $entityId, $parentField, $rows, $subFields): void {
            FieldRepeaterRow::query()
                ->where('entity_type', $entityType)
                ->where('entity_id', $entityId)
                ->where('field_id', $parentField->id)
                ->delete();

            foreach (array_values($rows) as $sortOrder => $rowValues) {
                if (! is_array($rowValues) || $rowValues === []) {
                    continue;
                }

                $row = FieldRepeaterRow::query()->create([
                    'entity_type' => $entityType,
                    'entity_id' => $entityId,
                    'field_id' => $parentField->id,
                    'row_index' => $sortOrder,
                    'sort_order' => $sortOrder,
                ]);

                foreach ($rowValues as $subKey => $value) {
                    $subField = $subFields->get((string) $subKey);

                    if (! $subField instanceof Field) {
                        continue;
                    }

                    if (! FieldValueCoercion::canCoerce($subField, $value)) {
                        continue;
                    }

                    $coerced = FieldValueCoercion::coerce($subField, $value);
                    $column = FieldTypes::valueColumn($subField->type);

                    FieldRepeaterValue::query()->create([
                        'field_repeater_row_id' => $row->id,
                        'sub_field_id' => $subField->id,
                        $column => $coerced,
                    ]);
                }
            }
        });
    }

    /**
     * @return Collection<string, Field>
     */
    private function subFieldIndex(Field $parentField): Collection
    {
        return Field::query()
            ->where('parent_field_id', $parentField->id)
            ->where('status', 'active')
            ->orderBy('sort_order')
            ->get()
            ->keyBy('key');
    }
}
