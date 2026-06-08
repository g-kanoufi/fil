<?php

declare(strict_types=1);

namespace App\Services\Fields;

use App\Models\Area;
use App\Models\Field;
use App\Models\FieldRelationLink;
use App\Models\FieldValue;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Support\Fields\FieldTypes;
use App\Support\Fields\FieldValueCoercion;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final class FieldValueWriter
{
    public function __construct(
        private readonly RepeaterValueWriter $repeaterValues,
    ) {}

    /**
     * Persist a map of field key => value for an entity. Relational fields write
     * to field_relation_links; scalar fields write typed slots in field_values.
     *
     * @param  array<string, mixed>  $values  keyed by field key
     */
    public function write(string $entityType, int $entityId, array $values): void
    {
        if ($values === []) {
            return;
        }

        $fields = Field::query()
            ->where('entity', $entityType)
            ->whereIn('key', array_keys($values))
            ->where('status', 'active')
            ->get()
            ->keyBy('key');

        DB::transaction(function () use ($entityType, $entityId, $values, $fields): void {
            foreach ($values as $key => $value) {
                $field = $fields->get($key);

                if (! $field instanceof Field) {
                    continue;
                }

                if (FieldTypes::isRelation($field->type)) {
                    $this->writeRelation($field, $entityType, $entityId, $value);

                    continue;
                }

                if (FieldTypes::isRepeater($field->type) && is_array($value)) {
                    /** @var list<array<string, mixed>> $rows */
                    $rows = array_values($value);
                    $this->repeaterValues->write($entityType, $entityId, $field, $rows);

                    continue;
                }

                $this->writeScalar($field, $entityType, $entityId, $value);
            }
        });
    }

    private function writeScalar(Field $field, string $entityType, int $entityId, mixed $value): void
    {
        if (in_array($field->storage, ['column', 'foreign_key'], true) && $field->maps_to_column) {
            $this->writeColumn($field, $entityType, $entityId, $value);

            return;
        }

        if (! FieldValueCoercion::canCoerce($field, $value)) {
            return;
        }

        $coerced = FieldValueCoercion::coerce($field, $value);
        $column = FieldTypes::valueColumn($field->type);

        FieldValue::query()->updateOrCreate(
            [
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'field_id' => $field->id,
            ],
            [$column => $coerced],
        );
    }

    private function writeColumn(Field $field, string $entityType, int $entityId, mixed $value): void
    {
        $modelClass = $this->entityModel($entityType);

        if ($modelClass === null) {
            return;
        }

        if (! FieldValueCoercion::canCoerce($field, $value)) {
            return;
        }

        $modelClass::query()
            ->whereKey($entityId)
            ->update([$field->maps_to_column => FieldValueCoercion::coerce($field, $value)]);
    }

    /**
     * @return class-string<Model>|null
     */
    private function entityModel(string $entityType): ?string
    {
        return match ($entityType) {
            'lead' => Lead::class,
            'store' => Store::class,
            'area' => Area::class,
            'organization' => Organization::class,
            default => null,
        };
    }

    private function writeRelation(Field $field, string $entityType, int $entityId, mixed $value): void
    {
        /** @var array<string, mixed> $config */
        $config = $field->config ?? [];
        $relatedType = (string) ($config['related_entity'] ?? '');

        if ($relatedType === '') {
            return;
        }

        $relatedIds = is_array($value) ? $value : [$value];
        $relatedIds = array_values(array_filter(array_map(
            static fn ($id): int => (int) $id,
            $relatedIds,
        )));

        FieldRelationLink::query()
            ->where('field_id', $field->id)
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->delete();

        foreach ($relatedIds as $index => $relatedId) {
            FieldRelationLink::query()->create([
                'field_id' => $field->id,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'related_type' => $relatedType,
                'related_id' => $relatedId,
                'sort_order' => $index,
            ]);
        }
    }
}
