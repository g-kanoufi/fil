<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\Field;
use Illuminate\Support\Collection;

final class LegacyMappingGapsService
{
    /** @var array<string, string> */
    private const ENTITY_POST_TYPES = [
        'lead' => 'application',
        'store' => 'store',
        'location' => 'franchise_location',
        'area' => 'area',
        'organization' => 'organization',
    ];

    public function __construct(
        private readonly LegacyExtrasKeyResolver $resolver,
        private readonly LegacyAcfFilePatternBuilder $filePatterns,
    ) {}

    /**
     * @return array{
     *     entity: string,
     *     post_type: string,
     *     legacy_posts: int,
     *     meta_rows: int,
     *     buckets: array<string, list<array{key: string, count: int, note?: string}>>
     * }
     */
    public function analyze(string $dumpPath, string $prefix, string $entity, int $minCount = 5): array
    {
        $postType = self::ENTITY_POST_TYPES[$entity] ?? null;

        if ($postType === null) {
            throw new \InvalidArgumentException("Unknown entity: {$entity}");
        }

        $postIds = $this->collectPostIds($dumpPath, $prefix.'posts', $postType);
        $fieldIndex = $this->fieldIndex($entity);
        $documentPatterns = $this->documentPatternsForEntity($entity);

        /** @var array<string, int> $keyCounts */
        $keyCounts = [];
        $metaRows = 0;

        foreach (LegacySqlInsertReader::statements($dumpPath, 'INSERT INTO `'.$prefix.'postmeta`') as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            foreach (LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 4) {
                    continue;
                }

                $postId = (int) ($fields[1] ?? 0);
                $metaKey = (string) ($fields[2] ?? '');

                if ($postId <= 0 || ! isset($postIds[$postId]) || $metaKey === '' || str_starts_with($metaKey, '_')) {
                    continue;
                }

                $metaRows++;
                $keyCounts[$metaKey] = ($keyCounts[$metaKey] ?? 0) + 1;
            }
        }

        $buckets = [
            'tier1' => [],
            'field' => [],
            'document' => [],
            'discard' => [],
            'out_of_scope' => [],
            'gap' => [],
        ];

        /** @var array<string, string> $tier1 */
        $tier1 = config('fil-legacy-acf.tier1_columns.'.$entity, []);

        foreach ($keyCounts as $metaKey => $count) {
            if ($count < $minCount) {
                continue;
            }

            if (isset($tier1[$metaKey])) {
                $buckets['tier1'][] = ['key' => $metaKey, 'count' => $count, 'note' => $tier1[$metaKey]];

                continue;
            }

            if ($this->matchesDocumentPattern($metaKey, $documentPatterns)) {
                $buckets['document'][] = ['key' => $metaKey, 'count' => $count];

                continue;
            }

            $resolved = $this->resolver->resolve($metaKey, $fieldIndex, $entity);

            if ($resolved?->isDiscard()) {
                $buckets['discard'][] = ['key' => $metaKey, 'count' => $count, 'note' => $this->outOfScopeNote($metaKey)];

                continue;
            }

            if ($resolved !== null) {
                $buckets['field'][] = ['key' => $metaKey, 'count' => $count];

                continue;
            }

            $outOfScope = $this->outOfScopeNote($metaKey);

            if ($outOfScope !== null) {
                $buckets['out_of_scope'][] = ['key' => $metaKey, 'count' => $count, 'note' => $outOfScope];

                continue;
            }

            $buckets['gap'][] = ['key' => $metaKey, 'count' => $count];
        }

        foreach ($buckets as $bucket => $rows) {
            usort($rows, static fn (array $a, array $b): int => $b['count'] <=> $a['count']);
            $buckets[$bucket] = $rows;
        }

        return [
            'entity' => $entity,
            'post_type' => $postType,
            'legacy_posts' => count($postIds),
            'meta_rows' => $metaRows,
            'buckets' => $buckets,
        ];
    }

    /**
     * @return array<int, true>
     */
    private function collectPostIds(string $dumpPath, string $tableName, string $postType): array
    {
        $ids = [];

        foreach (LegacySqlInsertReader::statements($dumpPath, 'INSERT INTO `'.$tableName.'`') as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            foreach (LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 21) {
                    continue;
                }

                if ((string) ($fields[20] ?? '') !== $postType) {
                    continue;
                }

                $legacyPostId = (int) ($fields[0] ?? 0);

                if ($legacyPostId > 0) {
                    $ids[$legacyPostId] = true;
                }
            }
        }

        return $ids;
    }

    /**
     * @return Collection<string, Field>
     */
    private function fieldIndex(string $entity): Collection
    {
        return Field::query()
            ->where('entity', $entity)
            ->where('status', 'active')
            ->get()
            ->keyBy('key');
    }

    /**
     * @return list<array{role: string, label: string, regex: string}>
     */
    private function documentPatternsForEntity(string $entity): array
    {
        /** @var array<string, string> $acfGroups */
        $acfGroups = config('fil-documents.acf_field_groups', []);
        $patterns = [];

        if ($entity === 'store' && isset($acfGroups['store'])) {
            $patterns = array_merge($patterns, $this->filePatterns->fromJsonFile(base_path($acfGroups['store'])));
        }

        if (in_array($entity, ['store', 'location'], true) && isset($acfGroups['franchise_location'])) {
            $patterns = array_merge($patterns, $this->filePatterns->fromJsonFile(base_path($acfGroups['franchise_location'])));
        }

        return $patterns;
    }

    /**
     * @param  list<array{role: string, label: string, regex: string}>  $patterns
     */
    private function matchesDocumentPattern(string $metaKey, array $patterns): bool
    {
        foreach ($patterns as $pattern) {
            if (preg_match($pattern['regex'], $metaKey) === 1) {
                return true;
            }
        }

        return false;
    }

    private function outOfScopeNote(string $metaKey): ?string
    {
        /** @var array<string, string> $notes */
        $notes = config('fil-legacy-acf.out_of_scope', []);

        foreach ($notes as $prefix => $note) {
            if ($metaKey === $prefix || str_starts_with($metaKey, $prefix)) {
                return $note;
            }
        }

        return null;
    }
}
