<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyInferFieldsService;
use Illuminate\Console\Command;

final class LegacyInferFieldsCommand extends Command
{
    protected $signature = 'legacy:infer-fields
                            {dump? : Path to .sql.gz dump}
                            {--prefix= : Legacy dump table prefix}
                            {--post-type=store : Legacy post type to analyze}
                            {--min=3 : Minimum row count to report an orphan key}';

    protected $description = 'List postmeta keys in the dump that are not registered in the ACF catalog for a post type.';

    public function handle(LegacyInferFieldsService $service): int
    {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) ($this->option('prefix') ?: config('fil.legacy.table_prefix'));
        $postType = (string) $this->option('post-type');
        $min = max(1, (int) $this->option('min'));

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        $report = $service->analyze($dump, $prefix, $postType, $min);

        $this->info("Orphan keys for {$report['post_type']} (entity {$report['entity']}, min {$min})");

        if ($report['orphan_keys'] === []) {
            $this->info('No orphan keys above threshold.');

            return self::SUCCESS;
        }

        $this->table(
            ['Meta key', 'Rows'],
            array_map(
                static fn (array $row): array => [$row['key'], (string) $row['count']],
                array_slice($report['orphan_keys'], 0, 50),
            ),
        );

        if (count($report['orphan_keys']) > 50) {
            $this->line('… '.(count($report['orphan_keys']) - 50).' more');
        }

        return self::FAILURE;
    }
}
