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
use App\Models\User;
use App\Support\Fields\FieldTypes;
use App\Support\Fields\LegacyPostTypeFieldScope;
use App\Support\Fields\NoteFieldCatalog;
use Illuminate\Database\Eloquent\Model;

final class EntityFieldValueReader
{
    public function __construct(
        private readonly RepeaterValueReader $repeaterValues,
    ) {}

    /**
     * @return array<string, mixed> keyed by field key
     */
    public function forEntity(string $entityType, int $entityId, ?string $legacyPostType = null): array
    {
        $fieldsQuery = Field::query()
            ->where('entity', $entityType)
            ->where('parent_field_id', 0)
            ->where('status', 'active')
            ->where('storage', 'field_value')
            ->orderBy('sort_order')
            ->orderBy('id');

        NoteFieldCatalog::applyExcludedFieldScope($fieldsQuery);
        LegacyPostTypeFieldScope::apply($fieldsQuery, $legacyPostType);

        $fields = $fieldsQuery->get();

        if ($fields->isEmpty()) {
            return [];
        }

        $values = FieldValue::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereIn('field_id', $fields->pluck('id'))
            ->get()
            ->keyBy('field_id');

        $relations = FieldRelationLink::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->whereIn('field_id', $fields->pluck('id'))
            ->orderBy('sort_order')
            ->get()
            ->groupBy('field_id');

        $result = [];

        foreach ($fields as $field) {
            if (FieldTypes::isRelation($field->type)) {
                $links = $relations->get($field->id, collect());
                $ids = $links->pluck('related_id')->all();
                $result[$field->key] = $field->type === FieldTypes::RELATION_MANY ? $ids : ($ids[0] ?? null);

                continue;
            }

            if (FieldTypes::isRepeater($field->type)) {
                $result[$field->key] = $this->repeaterValues->read($entityType, $entityId, $field);

                continue;
            }

            if (in_array($field->storage, ['column', 'foreign_key'], true) && $field->maps_to_column) {
                $model = $this->entityModel($entityType);

                if ($model !== null) {
                    $column = $field->maps_to_column;
                    $entity = $model::query()->find($entityId);
                    $result[$field->key] = $entity?->{$column};

                    continue;
                }
            }

            $row = $values->get($field->id);

            if ($row === null) {
                continue;
            }

            $result[$field->key] = $this->extractScalar($field->type, $row);
        }

        return $result;
    }

    private function extractScalar(string $type, FieldValue $row): mixed
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
            'contact' => User::class,
            default => null,
        };
    }
}
