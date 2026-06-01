<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyMappingGapsService;
use Illuminate\Console\Command;

final class LegacyMappingGapsCommand extends Command
{
    protected $signature = 'legacy:mapping-gaps
                            {dump? : Path to .sql.gz dump}
                            {--prefix=vnzokz0zw_9_ : Legacy dump table prefix}
                            {--entity=store : FIL entity (store, lead, location, area, organization)}
                            {--min=5 : Minimum postmeta row count to report a key}';

    protected $description = 'Report legacy postmeta keys not mapped to FIL fields, tier-1 columns, or documents.';

    public function handle(LegacyMappingGapsService $service): int
    {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        $entity = (string) $this->option('entity');
        $min = max(1, (int) $this->option('min'));

        $this->info("Analyzing {$entity} postmeta in {$dump} (min count {$min})…");

        $report = $service->analyze($dump, (string) $this->option('prefix'), $entity, $min);

        $this->line(sprintf(
            'Legacy %s posts: %d · postmeta rows scanned: %d',
            $report['post_type'],
            $report['legacy_posts'],
            $report['meta_rows'],
        ));

        $this->renderBucket('Tier-1 columns', $report['buckets']['tier1']);
        $this->renderBucket('Field schema / drain', $report['buckets']['field']);
        $this->renderBucket('Documents import', $report['buckets']['document']);
        $this->renderBucket('Discarded during drain', $report['buckets']['discard']);
        $this->renderBucket('Documented out-of-scope', $report['buckets']['out_of_scope']);
        $this->renderBucket('Unmapped gaps', $report['buckets']['gap'], true);

        if ($report['buckets']['gap'] !== []) {
            $this->warn('Review unmapped keys — add ACF fields, tier-1 columns, or document patterns, or document in docs/LEGACY_MAPPING_GAPS.md.');

            return self::FAILURE;
        }

        $this->info('No unmapped keys above threshold.');

        return self::SUCCESS;
    }

    /**
     * @param  list<array{key: string, count: int, note?: string}>  $rows
     */
    private function renderBucket(string $title, array $rows, bool $highlight = false): void
    {
        $this->newLine();
        $this->line("<fg=cyan>{$title}</> (".count($rows).')');

        if ($rows === []) {
            $this->line('  —');

            return;
        }

        $tableRows = [];

        foreach (array_slice($rows, 0, 25) as $row) {
            $tableRows[] = [
                $row['key'],
                (string) $row['count'],
                $row['note'] ?? '',
            ];
        }

        $this->table(['Meta key', 'Rows', 'Note'], $tableRows);

        if (count($rows) > 25) {
            $this->line('  … '.(count($rows) - 25).' more');
        }

        if ($highlight && $rows !== []) {
            $this->warn('Action required for unmapped keys.');
        }
    }
}
