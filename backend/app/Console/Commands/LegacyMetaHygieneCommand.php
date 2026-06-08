<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Field;
use App\Services\Legacy\LegacyExtrasDrainService;
use App\Services\Legacy\LegacyExtrasKeyResolver;
use App\Services\Legacy\LegacyNamedTableImporter;
use App\Services\Legacy\LegacyPostMetaHygiene;
use App\Services\Legacy\LegacyPostTypeIndex;
use App\Support\Fields\LegacyPostTypeFieldScope;
use App\Support\Legacy\LegacyPostTypeEntityMap;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

final class LegacyMetaHygieneCommand extends Command
{
    protected $signature = 'legacy:meta-hygiene
                            {dump? : Path to .sql.gz dump}
                            {--prefix= : Legacy dump table prefix}
                            {--post-type= : Limit report to one legacy post_type}';

    protected $description = 'Dry-run report of postmeta rows kept vs skipped by meta hygiene.';

    public function handle(
        LegacyNamedTableImporter $importer,
        LegacyPostTypeIndex $postTypes,
        LegacyPostMetaHygiene $hygiene,
        LegacyExtrasDrainService $extrasDrain,
        LegacyExtrasKeyResolver $keyResolver,
    ): int {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) ($this->option('prefix') ?: config('fil.legacy.table_prefix'));
        $filterPostType = $this->option('post-type');

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        $postTypes->build($dump, $prefix);
        $fieldIndexes = [];

        $stats = ['importable' => 0, 'skipped_orphan' => 0, 'skipped_discard' => 0, 'skipped_empty' => 0];

        $importer->import(
            $dump,
            $prefix.'postmeta',
            function (array $row, bool $execute) use (
                &$stats,
                &$fieldIndexes,
                $postTypes,
                $hygiene,
                $extrasDrain,
                $keyResolver,
                $filterPostType,
            ): void {
                unset($execute);

                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = $row['meta_value'] ?? null;
                $legacyPostId = (int) ($row['post_id'] ?? 0);

                if ($legacyPostId <= 0 || $metaKey === '' || str_starts_with($metaKey, '_')) {
                    return;
                }

                if ($metaValue === null || $metaValue === '') {
                    $stats['skipped_empty']++;

                    return;
                }

                $legacyPostType = $postTypes->typeFor($legacyPostId) ?? '';

                if ($filterPostType !== null && $legacyPostType !== (string) $filterPostType) {
                    return;
                }

                $entityType = LegacyPostTypeEntityMap::entityFor($legacyPostType);
                $fieldIndex = $fieldIndexes[$entityType.'|'.$legacyPostType] ??= self::fieldIndex($entityType, $legacyPostType);

                $fieldValueTarget = $extrasDrain->resolveFieldValueTarget($legacyPostId, $metaKey, (string) $metaValue, $legacyPostType);

                if ($hygiene->shouldImport($metaKey, $entityType, $fieldIndex, false, $fieldValueTarget)) {
                    $stats['importable']++;

                    return;
                }

                $resolved = $keyResolver->resolve($metaKey, $fieldIndex, $entityType);

                if ($resolved?->isDiscard() === true) {
                    $stats['skipped_discard']++;
                } else {
                    $stats['skipped_orphan']++;
                }
            },
            false,
        );

        $this->table(
            ['Bucket', 'Rows'],
            collect($stats)->map(fn (int $count, string $bucket) => [$bucket, $count])->values()->all(),
        );

        return self::SUCCESS;
    }

    /**
     * @return Collection<string, Field>
     */
    private static function fieldIndex(string $entityType, string $legacyPostType): Collection
    {
        $query = Field::query()
            ->where('entity', $entityType)
            ->where('status', 'active');

        LegacyPostTypeFieldScope::apply($query, $legacyPostType);

        return $query->get()->keyBy('key');
    }
}
