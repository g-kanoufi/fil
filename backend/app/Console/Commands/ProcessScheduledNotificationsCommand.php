<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notifications\ScheduledNotificationProcessor;
use App\Services\Notifications\StoreInspectionDueProcessor;
use Illuminate\Console\Command;

final class ProcessScheduledNotificationsCommand extends Command
{
    protected $signature = 'notifications:process-scheduled {--limit=200}';

    protected $description = 'Process date-based scheduled notification rules for leads and store inspections';

    public function handle(
        ScheduledNotificationProcessor $leadProcessor,
        StoreInspectionDueProcessor $inspectionProcessor,
    ): int {
        $limit = max(1, (int) $this->option('limit'));
        $leadDispatched = $leadProcessor->process($limit);
        $storeDispatched = $inspectionProcessor->process($limit);

        $this->info("Queued scheduled notification processing for {$leadDispatched} lead(s) and {$storeDispatched} store inspection(s).");

        return self::SUCCESS;
    }
}
