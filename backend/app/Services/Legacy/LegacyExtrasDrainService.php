<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FranchiseLocation;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use App\Services\Fields\FieldValueWriter;
use App\Support\Fields\FieldTypes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class LegacyExtrasDrainService
{
    public function __construct(
        private readonly FieldValueWriter $fieldValues,
        private readonly LegacyExtrasKeyResolver $keyResolver,
        private readonly LegacyAcfMetaKeyCatalog $metaKeyCatalog,
        private readonly LegacyRepeaterMetaAggregator $repeaterMeta,
    ) {}

    /**
     * @return array<string, array{promoted: int, remaining_keys: int, entities: int}>
     */
    public function drainAll(bool $execute): array
    {
        return [
            'leads' => $this->drainModel(Lead::class, 'lead', $execute),
            'stores' => $this->drainModel(Store::class, 'store', $execute),
            'areas' => $this->drainModel(Area::class, 'area', $execute),
            'organizations' => $this->drainModel(Organization::class, 'organization', $execute),
        ];
    }

    /**
     * @param  class-string<Model>  $model
     * @return array{promoted: int, remaining_keys: int, entities: int}
     */
    public function drainModel(string $model, string $entityType, bool $execute): array
    {
        /** @var Model $instance */
        $instance = new $model;

        if (! Schema::hasColumn($instance->getTable(), 'extras')) {
            return ['promoted' => 0, 'remaining_keys' => 0, 'entities' => 0];
        }

        $fieldIndex = Field::query()
            ->where('entity', $entityType)
            ->where('storage', 'field_value')
            ->where('status', 'active')
            ->get()
            ->keyBy('key');

        if ($fieldIndex->isEmpty()) {
            return ['promoted' => 0, 'remaining_keys' => 0, 'entities' => 0];
        }

        $this->ensureSharedFields($fieldIndex, $entityType);

        $stats = ['promoted' => 0, 'remaining_keys' => 0, 'entities' => 0];

        $model::query()
            ->whereNotNull('extras')
            ->when(
                Schema::getConnection()->getDriverName() === 'pgsql',
                fn ($query) => $query->whereRaw("extras::text NOT IN ('[]', '{}')"),
                fn ($query) => $query
                    ->where('extras', '!=', '[]')
                    ->where('extras', '!=', '{}'),
            )
            ->orderBy('id')
            ->each(function (Model $record) use ($fieldIndex, $entityType, $execute, &$stats): void {
                $extras = $record->getAttribute('extras');

                if (! is_array($extras) || $extras === []) {
                    return;
                }

                $stats['entities']++;

                [$toWrite, $remaining, $promoted, $relationUpdates, $repeaterRows] = $this->partitionExtras($extras, $fieldIndex, $entityType, $record);
                $stats['promoted'] += $promoted;
                $stats['remaining_keys'] += count($remaining);

                if (! $execute) {
                    return;
                }

                foreach ($relationUpdates as $update) {
                    $update['model']::query()
                        ->whereKey($update['id'])
                        ->update([$update['column'] => $update['value']]);
                }

                $this->repeaterMeta->writeRows($entityType, (int) $record->getKey(), $repeaterRows, $fieldIndex);

                if ($toWrite !== []) {
                    $this->fieldValues->write($entityType, (int) $record->getKey(), $this->normalizeFieldValues($toWrite, $fieldIndex));
                }

                $record->update(['extras' => $remaining === [] ? null : $remaining]);
            });

        return $stats;
    }

    /**
     * @return array{entity: string, entity_id: int, key: string, value: string}|null
     */
    public function resolveFieldValueTarget(
        int $legacyPostId,
        string $metaKey,
        string $metaValue,
        ?string $legacyPostType = null,
    ): ?array {
        $field = $this->resolveScalarField($metaKey, $legacyPostType);

        if ($field === null) {
            return null;
        }

        $entityId = $this->resolveEntityId($field->entity, $legacyPostId);

        if ($entityId === null) {
            return null;
        }

        return [
            'entity' => $field->entity,
            'entity_id' => $entityId,
            'key' => $field->key,
            'value' => $metaValue,
        ];
    }

    /**
     * @param  array<string, mixed>  $extras
     * @param  Collection<string, Field>  $fieldIndex
     * @return array{array<string, mixed>, array<string, mixed>, int, list<array{model: class-string, id: int, column: string, value: mixed}>, array<string, array<int, array<string, mixed>>>}
     */
    private function partitionExtras(array $extras, Collection $fieldIndex, string $entityType, Model $record): array
    {
        $scalars = [];
        /** @var array<string, array<int, array<string, mixed>>> $repeaterRows */
        $repeaterRows = [];
        /** @var array<string, array<string, array<int, array<string, mixed>>>> $nestedRepeaterRows */
        $nestedRepeaterRows = [];
        $remaining = [];
        $promoted = 0;
        /** @var list<array{model: class-string, id: int, column: string, value: mixed}> $relationUpdates */
        $relationUpdates = [];

        foreach ($extras as $key => $value) {
            $metaKey = (string) $key;
            $resolved = $this->keyResolver->resolve($metaKey, $fieldIndex, $entityType);

            if ($resolved?->isDiscard() === true) {
                $promoted++;

                continue;
            }

            if ($resolved?->isRelationColumn() === true) {
                $target = $this->keyResolver->relationColumnTarget($entityType, $metaKey);

                if ($target !== null) {
                    $relationUpdate = $this->resolveRelationColumnUpdate($record, $target, $value);

                    if ($relationUpdate !== null) {
                        $relationUpdates[] = $relationUpdate;
                        $promoted++;

                        continue;
                    }

                    if ($this->metaKeyCatalog->normalizeMetaKey($metaKey) === 'email') {
                        $promoted++;

                        continue;
                    }
                }
            }

            if ($resolved?->isScalar() === true) {
                $scalars[$resolved->fieldKey] = $value;
                $promoted++;

                continue;
            }

            if ($resolved?->isRepeaterRow() === true) {
                $repeaterRows[$resolved->fieldKey][$resolved->rowIndex][$resolved->subKey] = $value;
                $promoted++;

                continue;
            }

            if ($resolved?->isNestedRepeaterRow() === true) {
                $nestedRepeaterRows[$resolved->fieldKey][$resolved->nestedKey][$resolved->rowIndex][$resolved->subKey] = $value;
                $promoted++;

                continue;
            }

            if ($this->isRepeaterRowCount($metaKey, $value, $fieldIndex)) {
                $promoted++;

                continue;
            }

            $remaining[$metaKey] = $value;
        }

        $toWrite = array_merge($scalars, $this->repeaterMeta->jsonFallbackPayloads($repeaterRows, $fieldIndex));

        foreach ($nestedRepeaterRows as $fieldKey => $nestedGroups) {
            $payload = [];

            foreach ($nestedGroups as $nestedKey => $rows) {
                ksort($rows);
                $payload[$nestedKey] = array_values($rows);
            }

            $toWrite[$fieldKey] = json_encode($payload, JSON_THROW_ON_ERROR);
        }

        return [$toWrite, $remaining, $promoted, $relationUpdates, $repeaterRows];
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    private function isRepeaterRowCount(string $metaKey, mixed $value, Collection $fieldIndex): bool
    {
        if (! is_numeric($value)) {
            return false;
        }

        $field = $fieldIndex->get($metaKey) ?? $fieldIndex->get($this->metaKeyCatalog->normalizeMetaKey($metaKey));

        if (! $field instanceof Field) {
            return false;
        }

        /** @var array<string, mixed>|null $config */
        $config = $field->config;

        return ($config['legacy_acf_type'] ?? null) === 'repeater'
            || FieldTypes::isRepeater((string) $field->type);
    }

    /**
     * @param  array<string, mixed>  $values
     * @param  Collection<string, Field>  $fieldIndex
     * @return array<string, mixed>
     */
    private function normalizeFieldValues(array $values, Collection $fieldIndex): array
    {
        foreach ($values as $key => $value) {
            $field = $fieldIndex->get((string) $key);

            if (! $field instanceof Field) {
                continue;
            }

            /** @var array<string, mixed>|null $config */
            $config = $field->config ?? [];
            $relatedEntity = (string) ($config['related_entity'] ?? '');

            if ($relatedEntity === 'user' && is_numeric($value)) {
                $legacyUserId = (int) $value;
                $values[$key] = User::query()->where('legacy_user_id', $legacyUserId)->value('id')
                    ?? User::query()->whereKey($legacyUserId)->value('id')
                    ?? $value;
            }
        }

        return $values;
    }

    /**
     * @param  array{relation: string, column: string}  $target
     * @return array{model: class-string, id: int, column: string, value: mixed}|null
     */
    private function resolveRelationColumnUpdate(Model $record, array $target, mixed $value): ?array
    {
        $relationId = $record->getAttribute($target['relation']);

        if ($relationId === null && $record instanceof Lead && $target['relation'] === 'prospect_user_id' && is_string($value) && $value !== '') {
            $userId = User::query()->where('email', $value)->value('id');

            if ($userId !== null) {
                $record->update(['prospect_user_id' => (int) $userId]);
                $relationId = (int) $userId;
            }
        }

        if ($relationId === null) {
            return null;
        }

        return [
            'model' => User::class,
            'id' => (int) $relationId,
            'column' => $target['column'],
            'value' => $value,
        ];
    }

    /**
     * @param  Collection<string, Field>  $fieldIndex
     */
    private function ensureSharedFields(Collection $fieldIndex, string $entityType): void
    {
        /** @var array<string, list<string>> $shared */
        $shared = config('fil-legacy-acf.shared_field_keys', []);

        foreach ($shared as $fieldKey => $entities) {
            if (! in_array($entityType, $entities, true) || $fieldIndex->has($fieldKey)) {
                continue;
            }

            $source = Field::query()
                ->where('key', $fieldKey)
                ->where('status', 'active')
                ->whereIn('entity', $entities)
                ->orderBy('sort_order')
                ->first();

            if ($source === null) {
                continue;
            }

            $group = match ($entityType) {
                'organization' => FieldGroup::query()->where('key', 'organizations')->first(),
                'lead' => FieldGroup::query()->where('key', 'applications')->first(),
                'store' => FieldGroup::query()->where('key', 'units')->first(),
                'area' => FieldGroup::query()->where('key', 'areas')->first(),
                default => null,
            } ?? FieldGroup::query()->orderBy('sort_order')->first();

            if ($group === null) {
                continue;
            }

            Field::query()->updateOrCreate(
                [
                    'field_group_id' => $group->id,
                    'key' => $fieldKey,
                ],
                [
                    'entity' => $entityType,
                    'name' => $source->name,
                    'type' => $source->type,
                    'storage' => $source->storage,
                    'maps_to_column' => $source->maps_to_column,
                    'config' => $source->config,
                    'sort_order' => $source->sort_order,
                    'is_filterable' => false,
                    'status' => 'active',
                    'legacy_field_key' => $source->legacy_field_key,
                ],
            );

            $fieldIndex->put($fieldKey, Field::query()
                ->where('entity', $entityType)
                ->where('key', $fieldKey)
                ->first());
        }
    }

    private function resolveScalarField(string $metaKey, ?string $legacyPostType = null): ?Field
    {
        $fields = Field::query()
            ->where('storage', 'field_value')
            ->where('status', 'active')
            ->when(
                $legacyPostType !== null && $legacyPostType !== '',
                fn ($query) => $query->where(function ($scoped) use ($legacyPostType): void {
                    $scoped->where('legacy_post_type', '')
                        ->orWhere('legacy_post_type', $legacyPostType);
                }),
            )
            ->get()
            ->keyBy('key');

        $resolved = $this->keyResolver->resolve($metaKey, $fields);

        if ($resolved?->isScalar() !== true) {
            return null;
        }

        $field = $fields->get($resolved->fieldKey);

        return $field instanceof Field ? $field : null;
    }

    private function resolveEntityId(string $entity, int $legacyPostId): ?int
    {
        $id = match ($entity) {
            'lead' => Lead::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'store' => Store::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'area' => Area::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'organization' => Organization::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            'contact' => User::query()->where('legacy_user_id', $legacyPostId)->value('id'),
            'franchise_location' => FranchiseLocation::query()->where('legacy_post_id', $legacyPostId)->value('id'),
            default => null,
        };

        return $id !== null ? (int) $id : null;
    }
}
