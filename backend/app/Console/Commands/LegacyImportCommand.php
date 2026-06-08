<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Activity\ActivityRecorder;
use App\Services\Legacy\LegacyAchEnrollmentImportService;
use App\Services\Legacy\LegacyAchImportService;
use App\Services\Legacy\LegacyAiThreadImportService;
use App\Services\Legacy\LegacyClientOptionsImportService;
use App\Services\Legacy\LegacyCommunicationImportService;
use App\Services\Legacy\LegacyDocumentImportService;
use App\Services\Legacy\LegacyInterestRegionTermSyncService;
use App\Services\Legacy\LegacyNotificationImportService;
use App\Services\Legacy\LegacyPostImportService;
use App\Services\Legacy\LegacyPostMetaImportService;
use App\Services\Legacy\LegacyRoyaltyImportService;
use App\Services\Legacy\LegacyUserImportService;
use Illuminate\Console\Command;

final class LegacyImportCommand extends Command
{
    protected $signature = 'legacy:import
                            {dump? : Path to .sql.gz dump}
                            {--prefix= : Legacy dump table prefix}
                            {--only= : Comma-separated entities (default: all)}
                            {--execute : Persist rows (default is dry-run)}
                            {--force : Allow destructive import in staging/production}
                            {--confirm= : Required with --force --execute in staging/production (value: legacy-import)}
                            {--all-users : Import prospects too (default imports staff only)}';

    protected $description = 'Import legacy CRM dump data into FIL tables.';

    private const ALL_ENTITIES = [
        'leads', 'stores', 'areas', 'organizations', 'franchise_locations', 'fdds', 'closings',
        'users', 'communications', 'notifications', 'postmeta', 'ai_threads',
        'royalties', 'ach', 'ach_enrollment', 'documents', 'options',
    ];

    private const POST_ENTITIES = [
        'leads', 'stores', 'areas', 'organizations', 'franchise_locations', 'fdds', 'closings',
    ];

    public function handle(
        LegacyPostImportService $postImporter,
        LegacyUserImportService $userImporter,
        LegacyCommunicationImportService $communicationImporter,
        LegacyNotificationImportService $notificationImporter,
        LegacyPostMetaImportService $postMetaImporter,
        LegacyAiThreadImportService $aiThreadImporter,
        LegacyRoyaltyImportService $royaltyImporter,
        LegacyAchImportService $achImporter,
        LegacyAchEnrollmentImportService $achEnrollmentImporter,
        LegacyDocumentImportService $documentImporter,
        LegacyClientOptionsImportService $clientOptionsImporter,
        LegacyInterestRegionTermSyncService $interestRegionTermSync,
        ActivityRecorder $activity,
    ): int {
        $dump = $this->argument('dump') ?? (string) config('fil.legacy.dump_path');
        $prefix = (string) ($this->option('prefix') ?: config('fil.legacy.table_prefix'));
        $onlyOption = $this->option('only');
        $only = $onlyOption === null || $onlyOption === ''
            ? self::ALL_ENTITIES
            : array_values(array_filter(array_map(trim(...), explode(',', (string) $onlyOption))));
        $execute = (bool) $this->option('execute');
        $forced = (bool) $this->option('force');

        if ($execute && app()->environment('production', 'staging')) {
            if (! $forced) {
                $this->error('Refusing legacy import in staging/production without --force.');

                return self::FAILURE;
            }

            $expectedConfirm = (string) config('fil-security.legacy_import.confirm_token', 'legacy-import');
            $confirm = (string) ($this->option('confirm') ?? '');

            if ($confirm !== $expectedConfirm) {
                $this->error("Pass --confirm={$expectedConfirm} with --force --execute in staging/production.");

                return self::FAILURE;
            }

            $activity->record(
                category: 'import',
                action: 'imported',
                summary: 'Legacy dump import started',
                payload: [
                    'dump' => $dump,
                    'only' => $only,
                    'environment' => app()->environment(),
                ],
                source: 'cli',
            );
        } elseif ($execute && $forced && ! app()->environment('production', 'staging')) {
            $this->warn('Forced legacy import in local environment.');
        } elseif ($forced && ! $execute) {
            $this->warn('--force has no effect without --execute.');
        }

        if (! is_readable($dump)) {
            $this->error("Dump not readable: {$dump}");

            return self::FAILURE;
        }

        if (! $execute) {
            $this->warn('Dry run — pass --execute to write rows.');
        }

        $rows = [];

        if ($this->wantsAny($only, self::POST_ENTITIES)) {
            $postStats = $postImporter->import($dump, $prefix, $only, $execute);
            $rows[] = ['leads', $postStats['leads']];
            $rows[] = ['stores', $postStats['stores']];
            $rows[] = ['areas', $postStats['areas']];
            $rows[] = ['organizations', $postStats['organizations']];
            $rows[] = ['franchise_locations', $postStats['franchise_locations']];
            $rows[] = ['fdds', $postStats['fdds']];
            $rows[] = ['closings', $postStats['closings']];
            $rows[] = ['skipped post tuples', $postStats['skipped']];
        }

        if (in_array('users', $only, true)) {
            $relevantOnly = ! (bool) $this->option('all-users');
            $userStats = $userImporter->import($dump, $prefix, $execute, $relevantOnly);
            $rows[] = ['users', $userStats['users']];
            $rows[] = ['staff users', $userStats['staff']];
            $rows[] = ['prospect users', $userStats['prospects']];
            $rows[] = ['roles assigned', $userStats['roles_assigned']];
            $rows[] = ['skipped users', $userStats['skipped']];
        }

        if (in_array('communications', $only, true)) {
            $commStats = $communicationImporter->import($dump, $prefix, $execute);
            $rows[] = ['communications', $commStats['communications']];
            $rows[] = ['skipped communications', $commStats['skipped']];
        }

        if (in_array('notifications', $only, true)) {
            $ruleStats = $notificationImporter->import($dump, $prefix, $execute);
            $rows[] = ['notification_rules', $ruleStats['rules']];
            $rows[] = ['skipped notification_rules', $ruleStats['skipped']];
        }

        if (in_array('postmeta', $only, true)) {
            $termStats = $interestRegionTermSync->sync($dump, $prefix, $execute);
            $rows[] = ['interest_region legacy terms linked', $termStats['linked']];
            $rows[] = ['interest_region market regions created', $termStats['created']];
            $rows[] = ['interest_region term sync skipped', $termStats['skipped']];
            $rows[] = ['interest_region term sync unresolved', $termStats['unresolved']];

            $metaStats = $postMetaImporter->import($dump, $prefix, $execute);
            $rows[] = ['postmeta applied to columns', $metaStats['applied']];
            $rows[] = ['postmeta promoted to field_values', $metaStats['field_values']];
            $rows[] = ['postmeta repeater rows', $metaStats['repeaters'] ?? 0];
            $rows[] = ['postmeta staged in extras', $metaStats['extras']];
            $rows[] = ['skipped postmeta', $metaStats['skipped']];
        }

        if (in_array('ai_threads', $only, true)) {
            $aiStats = $aiThreadImporter->import($dump, $prefix, $execute);
            $rows[] = ['ai_threads', $aiStats['threads']];
            $rows[] = ['ai_messages', $aiStats['messages']];
            $rows[] = ['skipped ai rows', $aiStats['skipped']];
        }

        if (in_array('royalties', $only, true)) {
            $royaltyStats = $royaltyImporter->import($dump, $prefix, $execute);
            $rows[] = ['royalty_periods', $royaltyStats['periods']];
            $rows[] = ['royalty_line_items', $royaltyStats['line_items']];
            $rows[] = ['skipped royalties', $royaltyStats['skipped']];
        }

        if (in_array('ach', $only, true)) {
            $achStats = $achImporter->import($dump, $prefix, $execute);
            $rows[] = ['ach_transfers', $achStats['transfers']];
            $rows[] = ['skipped ach', $achStats['skipped']];
        }

        if (in_array('ach_enrollment', $only, true)) {
            $enrollmentStats = $achEnrollmentImporter->import($dump, $prefix, $execute);
            $rows[] = ['ach_customers', $enrollmentStats['customers']];
            $rows[] = ['ach_funding_sources', $enrollmentStats['funding_sources']];
            $rows[] = ['skipped ach_enrollment', $enrollmentStats['skipped']];
        }

        if (in_array('documents', $only, true)) {
            $documentStats = $documentImporter->import($dump, $prefix, $execute);
            $rows[] = ['documents', $documentStats['documents']];
            $rows[] = ['document_links', $documentStats['links']];
            $rows[] = ['skipped documents', $documentStats['skipped']];
        }

        if (in_array('options', $only, true)) {
            $optionsStats = $clientOptionsImporter->import($dump, $prefix, $execute);
            $rows[] = ['client_settings', $optionsStats['applied']];
            $rows[] = ['skipped client_settings', $optionsStats['skipped']];
        }

        $this->table(['Entity', 'Matched'], $rows);

        if ($execute) {
            $this->info('Import complete. Run `php artisan legacy:finalize` to verify migration readiness.');
        }

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $only
     * @param  list<string>  $entities
     */
    private function wantsAny(array $only, array $entities): bool
    {
        foreach ($entities as $entity) {
            if (in_array($entity, $only, true)) {
                return true;
            }
        }

        return false;
    }
}
