<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Activity\NavigationActivityRecorder;
use Illuminate\Console\Command;

final class ActivityPurgeNavigationCommand extends Command
{
    protected $signature = 'activity:purge-navigation';

    protected $description = 'Delete navigation activity rows past the purge retention window';

    public function handle(NavigationActivityRecorder $recorder): int
    {
        $purgeDays = (int) config('fil-activity.retention.navigation_purge_days', 90);
        $deleted = $recorder->purgeOlderThan($purgeDays);

        $this->info("Purged {$deleted} navigation activity row(s).");

        return self::SUCCESS;
    }
}
