<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Area;
use App\Models\User;
use Illuminate\Console\Command;

final class SyncAreaRepUserIdsCommand extends Command
{
    protected $signature = 'legacy:sync-area-reps {--dry-run : Report without writing}';

    protected $description = 'Normalize area rep user IDs from legacy select_reps / area_reps extras into rep_user_id';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $updated = 0;
        $skipped = 0;

        Area::query()->orderBy('id')->chunkById(50, function ($areas) use ($dryRun, &$updated, &$skipped): void {
            foreach ($areas as $area) {
                $extras = is_array($area->extras) ? $area->extras : [];

                if (filled($extras['rep_user_id'] ?? null)) {
                    $skipped++;

                    continue;
                }

                $repUserId = $this->resolveRepUserId($extras);

                if ($repUserId === null) {
                    $skipped++;

                    continue;
                }

                $extras['rep_user_id'] = $repUserId;

                if (! $dryRun) {
                    $area->update(['extras' => $extras]);
                }

                $updated++;
            }
        });

        $this->info(sprintf('Area reps: %d updated, %d skipped%s.', $updated, $skipped, $dryRun ? ' (dry run)' : ''));

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $extras
     */
    private function resolveRepUserId(array $extras): ?int
    {
        foreach (['rep_user_id', 'area_rep_user_id'] as $directKey) {
            if (filled($extras[$directKey] ?? null) && is_numeric($extras[$directKey])) {
                return $this->mapLegacyUserId((int) $extras[$directKey]);
            }
        }

        foreach (['select_reps', 'area_reps'] as $listKey) {
            $raw = $extras[$listKey] ?? null;

            if ($raw === null || $raw === '') {
                continue;
            }

            $candidates = is_array($raw) ? $raw : (preg_split('/\s*,\s*/', (string) $raw) ?: []);

            foreach ($candidates as $candidate) {
                if (is_numeric($candidate)) {
                    $mapped = $this->mapLegacyUserId((int) $candidate);

                    if ($mapped !== null) {
                        return $mapped;
                    }
                }

                if (is_string($candidate) && str_contains($candidate, '@')) {
                    $userId = User::query()->where('email', trim($candidate))->value('id');

                    if ($userId !== null) {
                        return (int) $userId;
                    }
                }
            }
        }

        return null;
    }

    private function mapLegacyUserId(int $legacyOrFilId): ?int
    {
        $byLegacy = User::query()->where('legacy_user_id', $legacyOrFilId)->value('id');

        if ($byLegacy !== null) {
            return (int) $byLegacy;
        }

        return User::query()->whereKey($legacyOrFilId)->value('id') !== null
            ? $legacyOrFilId
            : null;
    }
}
