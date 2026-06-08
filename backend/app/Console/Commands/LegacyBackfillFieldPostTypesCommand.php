<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Legacy\LegacyFieldPostTypeBackfillService;
use Illuminate\Console\Command;

final class LegacyBackfillFieldPostTypesCommand extends Command
{
    protected $signature = 'legacy:backfill-field-post-types {--dry-run : Report changes without writing}';

    protected $description = 'Set fields.legacy_post_type from bundled ACF group config (fixes empty post-type scoping).';

    public function handle(LegacyFieldPostTypeBackfillService $service): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Dry run — no database writes.');
        }

        $result = $service->backfill(! $dryRun);

        $this->info(sprintf(
            'Updated %d fields across %d field groups.',
            $result['fields'],
            $result['groups'],
        ));

        if ($result['skipped_groups'] !== []) {
            $this->warn('Skipped groups (no post type mapping): '.implode(', ', $result['skipped_groups']));
        }

        if ($dryRun && $result['fields'] > 0) {
            $this->line('Re-run without --dry-run to apply.');
        }

        return self::SUCCESS;
    }
}
