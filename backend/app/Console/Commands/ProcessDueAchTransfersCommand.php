<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Ach\AchTransferBatchService;
use Illuminate\Console\Command;

final class ProcessDueAchTransfersCommand extends Command
{
    protected $signature = 'ach:process-due-transfers';

    protected $description = 'Aggregate unpaid royalty line items into ACH transfers (legacy z_ach_cron_exec parity)';

    public function handle(AchTransferBatchService $service): int
    {
        if (! config('fil-royalties.enable_ach_royalty_collection')) {
            $this->warn('ACH royalty collection is disabled (FIL_ENABLE_ACH_COLLECTION).');

            return self::SUCCESS;
        }

        $stats = $service->processDueTransfers();

        $this->info(sprintf(
            'ACH: %d transfers, %d skipped, %d processed.',
            $stats['transfers'],
            $stats['skipped'],
            $stats['processed'],
        ));

        return self::SUCCESS;
    }
}
