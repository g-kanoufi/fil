<?php

declare(strict_types=1);

namespace App\Services\Activity;

use App\Models\ActivityNavigation;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class NavigationActivityRecorder
{
    public function __construct(
        private readonly NavigationPathResolver $paths,
    ) {}

    /**
     * @param  list<string>  $paths
     */
    public function upsertBatch(User $actor, array $paths): int
    {
        if (! Schema::hasTable('activity_navigation')) {
            return 0;
        }

        $recorded = 0;
        $periodBucket = CarbonImmutable::now('UTC')->toDateString();
        $now = now();

        foreach ($paths as $path) {
            $pathKey = $this->paths->normalizePath($path);

            if (! $this->paths->isAllowed($pathKey)) {
                continue;
            }

            $subject = $this->paths->resolveSubject($pathKey);

            $existing = ActivityNavigation::query()
                ->where('actor_user_id', $actor->id)
                ->where('path_key', $pathKey)
                ->whereDate('period_bucket', $periodBucket)
                ->first();

            if ($existing !== null) {
                $existing->forceFill([
                    'last_seen_at' => $now,
                    'view_count' => $existing->view_count + 1,
                ])->save();

                $recorded++;

                continue;
            }

            ActivityNavigation::query()->create([
                'actor_user_id' => $actor->id,
                'actor_name' => $actor->name,
                'path_key' => $pathKey,
                'period_bucket' => $periodBucket,
                'subject_type' => $subject['type'],
                'subject_id' => $subject['id'],
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'view_count' => 1,
                'source' => 'app',
            ]);

            $recorded++;
        }

        return $recorded;
    }

    public function purgeOlderThan(int $days): int
    {
        $cutoff = now()->subDays(max($days, 1));

        return DB::table('activity_navigation')
            ->where('last_seen_at', '<', $cutoff)
            ->delete();
    }
}
