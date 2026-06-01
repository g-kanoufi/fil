<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\InterestRegion;
use Illuminate\Support\Str;

final class LegacyInterestRegionTermSyncService
{
    private const TAXONOMY = 'grabba_tax_area';

    /** @var array<string, string> */
    private const COUNTRY_SLUG_CODES = [
        'usa' => 'US',
        'united-states' => 'US',
        'us' => 'US',
        'canada' => 'CA',
        'ca' => 'CA',
    ];

    /**
     * @return array{
     *     terms: int,
     *     linked: int,
     *     created: int,
     *     skipped: int,
     *     unresolved: int
     * }
     */
    public function sync(string $dumpPath, string $prefix, bool $execute): array
    {
        $terms = $this->loadTerms($dumpPath, $prefix);
        $taxonomy = $this->loadTaxonomy($dumpPath, $prefix);

        $stats = [
            'terms' => count($taxonomy),
            'linked' => 0,
            'created' => 0,
            'skipped' => 0,
            'unresolved' => 0,
        ];

        /** @var array<int, int> $legacyTermToRegionId */
        $legacyTermToRegionId = [];

        $orderedTermIds = $this->orderTermIds($taxonomy);

        foreach ($orderedTermIds as $termId) {
            $term = $terms[$termId] ?? null;
            $meta = $taxonomy[$termId] ?? null;

            if ($term === null || $meta === null) {
                $stats['unresolved']++;

                continue;
            }

            $existing = InterestRegion::query()->where('legacy_term_id', $termId)->first();

            if ($existing !== null) {
                $legacyTermToRegionId[$termId] = (int) $existing->id;
                $stats['skipped']++;

                continue;
            }

            if ($meta['parent'] === 0) {
                $region = $this->resolveCountryRegion($term);

                if ($region === null) {
                    $stats['unresolved']++;

                    continue;
                }

                if ($execute) {
                    $region->update(['legacy_term_id' => $termId]);
                }

                $legacyTermToRegionId[$termId] = (int) $region->id;
                $stats['linked']++;

                continue;
            }

            $parentRegionId = $legacyTermToRegionId[$meta['parent']]
                ?? InterestRegion::query()->where('legacy_term_id', $meta['parent'])->value('id');

            if ($parentRegionId === null) {
                $stats['unresolved']++;

                continue;
            }

            $parentRegionId = (int) $parentRegionId;
            $matched = $this->matchSubdivision($parentRegionId, $term);

            if ($matched !== null) {
                if ($execute) {
                    $matched->update(['legacy_term_id' => $termId]);
                }

                $legacyTermToRegionId[$termId] = (int) $matched->id;
                $stats['linked']++;

                continue;
            }

            if (! $execute) {
                $stats['created']++;
                $legacyTermToRegionId[$termId] = -1;

                continue;
            }

            $created = InterestRegion::query()->create([
                'parent_id' => $parentRegionId,
                'name' => $term['name'],
                'code' => $this->subdivisionCode($term),
                'slug' => $this->uniqueChildSlug($parentRegionId, $term['slug']),
                'sort_order' => $this->nextChildSortOrder($parentRegionId),
                'status' => 'active',
                'legacy_term_id' => $termId,
            ]);

            $legacyTermToRegionId[$termId] = (int) $created->id;
            $stats['created']++;
        }

        return $stats;
    }

    /**
     * @return array<int, array{name: string, slug: string}>
     */
    private function loadTerms(string $dumpPath, string $prefix): array
    {
        /** @var array<int, array{name: string, slug: string}> $terms */
        $terms = [];

        foreach (LegacySqlInsertReader::statements($dumpPath, 'INSERT INTO `'.$prefix.'terms`') as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            foreach (LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 3 || $fields[1] === null || $fields[2] === null) {
                    continue;
                }

                $terms[(int) $fields[0]] = [
                    'name' => (string) $fields[1],
                    'slug' => (string) $fields[2],
                ];
            }
        }

        return $terms;
    }

    /**
     * @return array<int, array{parent: int}>
     */
    private function loadTaxonomy(string $dumpPath, string $prefix): array
    {
        /** @var array<int, array{parent: int}> $taxonomy */
        $taxonomy = [];

        foreach (LegacySqlInsertReader::statements($dumpPath, 'INSERT INTO `'.$prefix.'term_taxonomy`') as $statement) {
            $valuesPos = stripos($statement, 'VALUES');

            if ($valuesPos === false) {
                continue;
            }

            foreach (LegacySqlFieldParser::splitTuples(substr($statement, $valuesPos + 6)) as $tuple) {
                $fields = LegacySqlFieldParser::parseFields($tuple);

                if (count($fields) < 6 || $fields[2] !== self::TAXONOMY) {
                    continue;
                }

                $taxonomy[(int) $fields[1]] = [
                    'parent' => (int) $fields[4],
                ];
            }
        }

        return $taxonomy;
    }

    /**
     * @param  array<int, array{parent: int}>  $taxonomy
     * @return list<int>
     */
    private function orderTermIds(array $taxonomy): array
    {
        $termIds = array_keys($taxonomy);

        usort($termIds, static function (int $left, int $right) use ($taxonomy): int {
            $leftDepth = $taxonomy[$left]['parent'] === 0 ? 0 : 1;
            $rightDepth = $taxonomy[$right]['parent'] === 0 ? 0 : 1;

            if ($leftDepth !== $rightDepth) {
                return $leftDepth <=> $rightDepth;
            }

            return $left <=> $right;
        });

        return $termIds;
    }

    /**
     * @param  array{name: string, slug: string}  $term
     */
    private function resolveCountryRegion(array $term): ?InterestRegion
    {
        $slug = Str::lower($term['slug']);
        $code = self::COUNTRY_SLUG_CODES[$slug] ?? null;

        if ($code !== null) {
            $byCode = InterestRegion::query()
                ->whereNull('parent_id')
                ->where('code', $code)
                ->first();

            if ($byCode !== null) {
                return $byCode;
            }
        }

        return InterestRegion::query()
            ->whereNull('parent_id')
            ->where(function ($query) use ($term, $slug): void {
                $query->where('slug', Str::slug($term['name']))
                    ->orWhere('slug', $slug)
                    ->orWhereRaw('LOWER(name) = ?', [Str::lower($term['name'])]);
            })
            ->first();
    }

    /**
     * @param  array{name: string, slug: string}  $term
     */
    private function matchSubdivision(int $parentRegionId, array $term): ?InterestRegion
    {
        $slug = Str::lower($term['slug']);
        $name = Str::lower($term['name']);

        $query = InterestRegion::query()
            ->where('parent_id', $parentRegionId)
            ->whereNull('legacy_term_id');

        if (strlen($slug) === 2 && ctype_alpha($slug)) {
            $byCode = (clone $query)->where('code', strtoupper($slug))->first();

            if ($byCode !== null) {
                return $byCode;
            }
        }

        $bySlug = (clone $query)->where('slug', Str::slug($term['slug']))->first();

        if ($bySlug !== null) {
            return $bySlug;
        }

        return (clone $query)->whereRaw('LOWER(name) = ?', [$name])->first();
    }

    /**
     * @param  array{name: string, slug: string}  $term
     */
    private function subdivisionCode(array $term): ?string
    {
        $slug = Str::lower($term['slug']);

        if (strlen($slug) === 2 && ctype_alpha($slug)) {
            return strtoupper($slug);
        }

        return null;
    }

    private function uniqueChildSlug(int $parentRegionId, string $legacySlug): string
    {
        $base = Str::slug($legacySlug) ?: 'legacy-region';
        $slug = $base;
        $suffix = 2;

        while (InterestRegion::query()->where('parent_id', $parentRegionId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function nextChildSortOrder(int $parentRegionId): int
    {
        return ((int) InterestRegion::query()->where('parent_id', $parentRegionId)->max('sort_order')) + 1;
    }
}
