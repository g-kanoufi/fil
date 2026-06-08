<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Area;
use App\Models\Field;
use App\Models\FranchiseLocation;
use App\Models\InterestRegion;
use App\Models\Lead;
use App\Models\Organization;
use App\Models\Store;
use App\Models\User;
use App\Services\Fields\FieldValueWriter;
use App\Services\Leads\LeadPipelineCatalog;
use App\Support\Fields\LegacyPostTypeFieldScope;
use App\Support\Legacy\LegacyPostTypeEntityMap;
use Illuminate\Support\Collection;

final class LegacyPostMetaImportService
{
    /** @var array<string, string> */
    private const LEAD_COLUMNS = [
        'lead_status' => 'lead_status',
        'lead_stage' => 'lead_stage',
        'lead_fdd_status' => 'lead_fdd_status',
        'lead_temp' => 'lead_temp',
        'lead_source' => 'lead_source',
        'likelihood_to_close' => 'likelihood_to_close',
    ];

    /** @var array<string, string> */
    private const STORE_COLUMNS = [
        'store_status' => 'store_status',
        'spa_id' => 'spa_id',
        'pos_provider' => 'pos_provider',
        'pos_external_id' => 'pos_external_id',
    ];

    /** @var array<string, string> */
    private const AREA_COLUMNS = [
        'approval_status' => 'status',
    ];

    /** @var array<string, string> */
    private const LOCATION_COLUMNS = [
        'status' => 'location_status',
    ];

    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
        private readonly LeadPipelineCatalog $pipelineCatalog,
        private readonly LegacyExtrasDrainService $extrasDrain,
        private readonly FieldValueWriter $fieldValues,
        private readonly LegacyPostTypeIndex $postTypes,
        private readonly LegacyPostMetaHygiene $metaHygiene,
        private readonly LegacyPostMetaRepeaterBuffer $repeaterBuffer,
    ) {}

    /**
     * @return array{applied: int, field_values: int, extras: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $stats = ['applied' => 0, 'field_values' => 0, 'extras' => 0, 'skipped' => 0, 'repeaters' => 0];
        $fieldColumnMap = $this->fieldColumnMap();
        $this->postTypes->build($dumpPath, $prefix);
        /** @var array<string, Collection<string, Field>> $fieldIndexes */
        $fieldIndexes = [];
        $metaHygieneEnabled = (bool) config('fil-legacy-acf.meta_hygiene', true);

        $result = $this->importer->import(
            $dumpPath,
            $prefix.'postmeta',
            function (array $row, bool $execute) use (&$stats, $fieldColumnMap, &$fieldIndexes, $metaHygieneEnabled): void {
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = $row['meta_value'] ?? null;
                $legacyPostId = (int) ($row['post_id'] ?? 0);

                if ($legacyPostId <= 0 || $metaKey === '' || str_starts_with($metaKey, '_')) {
                    $stats['skipped']++;

                    return;
                }

                if ($metaValue === null || $this->metaHygiene->isEmptyValue($metaValue)) {
                    $stats['skipped']++;

                    return;
                }

                if (! $this->metaHygiene->isEligiblePost($legacyPostId)) {
                    $stats['skipped']++;

                    return;
                }

                if (in_array($metaKey, ['select_reps', 'area_reps'], true)) {
                    $stats['applied']++;

                    if ($execute) {
                        $this->applyAreaRepMeta($legacyPostId, $metaKey, (string) $metaValue);
                    }

                    return;
                }

                $legacyPostType = $this->postTypes->typeFor($legacyPostId) ?? '';
                $entityType = LegacyPostTypeEntityMap::entityFor($legacyPostType);
                $indexKey = $entityType.'|'.$legacyPostType;
                $fieldIndexes[$indexKey] ??= $this->scopedFieldIndex($entityType, $legacyPostType);

                if ($this->repeaterBuffer->absorb(
                    $legacyPostId,
                    $entityType,
                    $legacyPostType,
                    $metaKey,
                    $metaValue,
                    $fieldIndexes[$indexKey],
                )) {
                    $stats['repeaters']++;

                    return;
                }

                $target = $this->resolveTarget($legacyPostId, $metaKey, (string) $metaValue, $fieldColumnMap);

                if ($target === null) {
                    $fieldValue = $this->extrasDrain->resolveFieldValueTarget(
                        $legacyPostId,
                        $metaKey,
                        (string) $metaValue,
                        $legacyPostType,
                    );

                    if ($metaHygieneEnabled) {
                        if (! $this->metaHygiene->shouldImport(
                            $metaKey,
                            $entityType,
                            $fieldIndexes[$indexKey],
                            false,
                            $fieldValue,
                            $legacyPostId,
                        )) {
                            $stats['skipped']++;

                            return;
                        }
                    }

                    if ($fieldValue !== null) {
                        $stats['field_values']++;

                        if ($execute) {
                            $this->fieldValues->write(
                                $fieldValue['entity'],
                                $fieldValue['entity_id'],
                                [$fieldValue['key'] => $fieldValue['value']],
                            );
                        }

                        return;
                    }

                    $stats['extras']++;

                    if ($execute) {
                        $this->appendExtra($legacyPostId, $metaKey, (string) $metaValue);
                    }

                    return;
                }

                $stats['applied']++;

                if ($execute) {
                    $target['model']::query()
                        ->whereKey($target['id'])
                        ->update([$target['column'] => $target['value']]);
                }
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

        if ($execute) {
            $repeaterStats = $this->repeaterBuffer->flush(true);
        } else {
            $repeaterStats = $this->repeaterBuffer->flush(false);
        }

        $stats['repeaters'] = $repeaterStats['written'];
        $stats['skipped'] += $repeaterStats['skipped'];

        return $stats;
    }

    /**
     * @param  array<string, array{entity: string, column: string}>  $fieldColumnMap
     * @return array{model: class-string, id: int, column: string, value: mixed}|null
     */
    private function resolveTarget(int $legacyPostId, string $metaKey, string $metaValue, array $fieldColumnMap): ?array
    {
        if (in_array($metaKey, ['area', 'area_cpt_store'], true)) {
            $storeId = Store::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($storeId === null) {
                return null;
            }

            $areaId = $this->resolveAreaReferenceId((string) $metaValue);

            return $areaId !== null ? [
                'model' => Store::class,
                'id' => (int) $storeId,
                'column' => 'area_id',
                'value' => $areaId,
            ] : null;
        }

        if ($metaKey === 'area_of_interest') {
            $leadId = Lead::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($leadId === null) {
                return null;
            }

            $interestRegionId = $this->resolveInterestRegionReferenceId((string) $metaValue);

            return $interestRegionId !== null ? [
                'model' => Lead::class,
                'id' => (int) $leadId,
                'column' => 'interest_region_id',
                'value' => $interestRegionId,
            ] : null;
        }

        if ($metaKey === 'lead_owner') {
            $leadId = Lead::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($leadId === null) {
                return null;
            }

            $ownerId = User::query()->where('legacy_user_id', (int) $metaValue)->value('id')
                ?? User::query()->whereKey((int) $metaValue)->value('id');

            return $ownerId ? [
                'model' => Lead::class,
                'id' => (int) $leadId,
                'column' => 'owner_user_id',
                'value' => (int) $ownerId,
            ] : null;
        }

        if ($metaKey === 'lead_progress') {
            $leadId = Lead::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($leadId === null) {
                return null;
            }

            return [
                'model' => Lead::class,
                'id' => (int) $leadId,
                'column' => 'pipeline_phase',
                'value' => $this->pipelineCatalog->normalizeLegacyProgress($metaValue),
            ];
        }

        if (isset(self::LEAD_COLUMNS[$metaKey])) {
            $leadId = Lead::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($leadId === null) {
                return null;
            }

            return [
                'model' => Lead::class,
                'id' => (int) $leadId,
                'column' => self::LEAD_COLUMNS[$metaKey],
                'value' => $metaKey === 'likelihood_to_close' ? (float) $metaValue : $metaValue,
            ];
        }

        if (isset(self::STORE_COLUMNS[$metaKey])) {
            $storeId = Store::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($storeId === null) {
                return null;
            }

            return [
                'model' => Store::class,
                'id' => (int) $storeId,
                'column' => self::STORE_COLUMNS[$metaKey],
                'value' => $metaValue,
            ];
        }

        if (isset(self::AREA_COLUMNS[$metaKey])) {
            $areaId = Area::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($areaId === null) {
                return null;
            }

            return [
                'model' => Area::class,
                'id' => (int) $areaId,
                'column' => self::AREA_COLUMNS[$metaKey],
                'value' => $metaValue,
            ];
        }

        if ($metaKey === 'store_no') {
            $locationId = FranchiseLocation::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($locationId === null) {
                return null;
            }

            $storeId = Store::query()->where('legacy_post_id', (int) $metaValue)->value('id')
                ?? Store::query()->whereKey((int) $metaValue)->value('id');

            return $storeId ? [
                'model' => FranchiseLocation::class,
                'id' => (int) $locationId,
                'column' => 'store_id',
                'value' => (int) $storeId,
            ] : null;
        }

        if (isset(self::LOCATION_COLUMNS[$metaKey])) {
            $locationId = FranchiseLocation::query()->where('legacy_post_id', $legacyPostId)->value('id');

            if ($locationId === null) {
                return null;
            }

            return [
                'model' => FranchiseLocation::class,
                'id' => (int) $locationId,
                'column' => self::LOCATION_COLUMNS[$metaKey],
                'value' => $metaValue,
            ];
        }

        if (isset($fieldColumnMap[$metaKey])) {
            return $this->resolveFieldColumnTarget($legacyPostId, $fieldColumnMap[$metaKey], $metaValue);
        }

        return null;
    }

    /**
     * @param  array{entity: string, column: string}  $mapping
     * @return array{model: class-string, id: int, column: string, value: mixed}|null
     */
    private function resolveFieldColumnTarget(int $legacyPostId, array $mapping, string $metaValue): ?array
    {
        $model = match ($mapping['entity']) {
            'lead', 'application' => Lead::class,
            'store' => Store::class,
            'area' => Area::class,
            'organization' => Organization::class,
            'franchise_location' => FranchiseLocation::class,
            default => null,
        };

        if ($model === null) {
            return null;
        }

        $id = $model::query()->where('legacy_post_id', $legacyPostId)->value('id');

        if ($id === null) {
            return null;
        }

        return [
            'model' => $model,
            'id' => (int) $id,
            'column' => $mapping['column'],
            'value' => $metaValue,
        ];
    }

    private function appendExtra(int $legacyPostId, string $metaKey, string $metaValue): void
    {
        foreach ([Lead::class, Store::class, Area::class, Organization::class, FranchiseLocation::class] as $model) {
            $record = $model::query()->where('legacy_post_id', $legacyPostId)->first();

            if ($record === null) {
                continue;
            }

            $extras = $record->extras ?? [];
            $extras[$metaKey] = $metaValue;
            $record->update(['extras' => $extras]);

            return;
        }
    }

    private function applyAreaRepMeta(int $legacyPostId, string $metaKey, string $metaValue): void
    {
        $area = Area::query()->where('legacy_post_id', $legacyPostId)->first();

        if ($area === null) {
            return;
        }

        $extras = is_array($area->extras) ? $area->extras : [];
        $extras[$metaKey] = $metaValue;

        $repUserId = $this->resolveAreaRepUserId($metaValue);

        if ($repUserId !== null) {
            $extras['rep_user_id'] = $repUserId;
        }

        $area->update(['extras' => $extras]);
    }

    private function resolveAreaRepUserId(string $metaValue): ?int
    {
        $candidates = preg_split('/\s*,\s*/', $metaValue) ?: [];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);

            if ($candidate === '') {
                continue;
            }

            if (is_numeric($candidate)) {
                $legacyId = (int) $candidate;
                $userId = User::query()->where('legacy_user_id', $legacyId)->value('id')
                    ?? User::query()->whereKey($legacyId)->value('id');

                if ($userId !== null) {
                    return (int) $userId;
                }
            }

            if (str_contains($candidate, '@')) {
                $userId = User::query()->where('email', $candidate)->value('id');

                if ($userId !== null) {
                    return (int) $userId;
                }
            }
        }

        return null;
    }

    private function resolveAreaReferenceId(string $metaValue): ?int
    {
        $reference = (int) trim($metaValue);

        if ($reference <= 0) {
            return null;
        }

        $areaId = Area::query()->where('legacy_post_id', $reference)->value('id');

        if ($areaId !== null) {
            return (int) $areaId;
        }

        return Area::query()->whereKey($reference)->exists()
            ? $reference
            : null;
    }

    private function resolveInterestRegionReferenceId(string $metaValue): ?int
    {
        $reference = (int) trim($metaValue);

        if ($reference <= 0) {
            return null;
        }

        $regionId = InterestRegion::query()->where('legacy_term_id', $reference)->value('id');

        if ($regionId !== null) {
            return (int) $regionId;
        }

        return InterestRegion::query()->whereKey($reference)->exists()
            ? $reference
            : null;
    }

    /**
     * @return array<string, array{entity: string, column: string}>
     */
    private function fieldColumnMap(): array
    {
        $map = [];

        foreach (Field::query()->where('storage', 'column')->whereNotNull('maps_to_column')->get() as $field) {
            $map[$field->key] = [
                'entity' => $field->entity,
                'column' => (string) $field->maps_to_column,
            ];
        }

        return $map;
    }

    private function scopedFieldIndex(string $entityType, string $legacyPostType): Collection
    {
        $query = Field::query()
            ->where('entity', $entityType)
            ->where('status', 'active');

        LegacyPostTypeFieldScope::apply($query, $legacyPostType);

        return $query->get()->keyBy('key');
    }
}
