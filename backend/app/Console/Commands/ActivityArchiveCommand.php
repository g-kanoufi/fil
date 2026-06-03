<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class ActivityArchiveCommand extends Command
{
    protected $signature = 'activity:archive';

    protected $description = 'Archive business activity_events older than the hot retention window';

    public function handle(): int
    {
        $hotDays = (int) config('fil-activity.retention.business_hot_days', 90);
        $cutoff = now()->subDays(max($hotDays, 1));

        $rows = DB::table('activity_events')
            ->where('occurred_at', '<', $cutoff)
            ->orderBy('id')
            ->get();

        if ($rows->isEmpty()) {
            $this->info('No business activity rows to archive.');

            return self::SUCCESS;
        }

        $payload = $rows->map(fn ($row): array => (array) $row)->all();

        DB::table('activity_events_archive')->insert($payload);

        $deleted = DB::table('activity_events')
            ->where('occurred_at', '<', $cutoff)
            ->delete();

        $this->info("Archived and removed {$deleted} business activity row(s).");

        return self::SUCCESS;
    }
}
