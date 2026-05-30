<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notifications\ScheduledNotificationProcessor;
use Illuminate\Console\Command;

final class ProcessScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:process-scheduled {--limit=200}';

    protected $description = 'Process date-based scheduled notification rules for leads';

    public function handle(ScheduledNotificationProcessor $processor): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $dispatched = $processor->process($limit);

        $this->info("Queued scheduled notification processing for {$dispatched} lead(s).");

        return self::SUCCESS;
    }
}
