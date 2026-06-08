<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use App\Support\Legacy\LegacyBundledAcfFieldCatalog;
use App\Support\Legacy\LegacyPostTypeEntityMap;

final class LegacyInferFieldsService
{
    public function __construct(
        private readonly LegacyBundledAcfFieldCatalog $bundledFields,
        private readonly LegacyPostTypeIndex $postTypes,
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{
     *     post_type: string,
     *     entity: string,
     *     registered_keys: list<string>,
     *     orphan_keys: list<array{key: string, count: int}>
     * }
     */
    public function analyze(string $dumpPath, string $prefix, string $legacyPostType, int $minCount = 3): array
    {
        $this->postTypes->build($dumpPath, $prefix);

        $entity = LegacyPostTypeEntityMap::entityFor($legacyPostType);

        $registered = $this->registeredKeysForPostType($legacyPostType, $entity);
        $postIds = $this->postIdsForType($dumpPath, $prefix, $legacyPostType);

        /** @var array<string, int> $keyCounts */
        $keyCounts = [];

        $this->importer->import(
            $dumpPath,
            $prefix.'postmeta',
            function (array $row) use (&$keyCounts, $postIds): void {
                $legacyPostId = (int) ($row['post_id'] ?? 0);
                $metaKey = (string) ($row['meta_key'] ?? '');

                if ($legacyPostId <= 0 || $metaKey === '' || str_starts_with($metaKey, '_')) {
                    return;
                }

                if (! isset($postIds[$legacyPostId])) {
                    return;
                }

                $keyCounts[$metaKey] = ($keyCounts[$metaKey] ?? 0) + 1;
            },
            false,
        );

        $orphans = [];

        foreach ($keyCounts as $key => $count) {
            if ($count < $minCount) {
                continue;
            }

            $normalized = preg_replace('/_\d+_.*$/', '', $key) ?? $key;

            if (! in_array($key, $registered, true) && ! in_array($normalized, $registered, true)) {
                $orphans[] = ['key' => $key, 'count' => $count];
            }
        }

        usort($orphans, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);

        return [
            'post_type' => $legacyPostType,
            'entity' => $entity,
            'registered_keys' => $registered,
            'orphan_keys' => $orphans,
        ];
    }

    /**
     * @return list<string>
     */
    private function registeredKeysForPostType(string $legacyPostType, string $entity): array
    {
        $keys = Field::query()
            ->where('entity', $entity)
            ->where('status', 'active')
            ->where(function ($query) use ($legacyPostType): void {
                $query->where('legacy_post_type', '')
                    ->orWhere('legacy_post_type', $legacyPostType);
            })
            ->pluck('key')
            ->all();

        $bundled = $this->bundledFields->fieldKeysForPostType($legacyPostType, $entity);

        /** @var array<string, array<string, string>> $tier1 */
        $tier1 = config('fil-legacy-acf.tier1_columns', []);
        $tierKeys = array_keys($tier1[$entity] ?? []);

        return array_values(array_unique(array_merge($keys, $bundled, $tierKeys)));
    }

    /**
     * @return array<int, true>
     */
    private function postIdsForType(string $dumpPath, string $prefix, string $legacyPostType): array
    {
        $ids = [];

        foreach ($this->postTypes->typesByLegacyPostId() as $postId => $type) {
            if ($type === $legacyPostType && $this->postTypes->isEligible($postId)) {
                $ids[$postId] = true;
            }
        }

        return $ids;
    }
}
