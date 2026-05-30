<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Royalties\RoyaltyBatchCalculationService;
use Illuminate\Console\Command;

final class CalculateDueRoyaltiesCommand extends Command
{
    protected $signature = 'royalties:calculate-due';

    protected $description = 'Calculate royalty periods for eligible stores (legacy calculate_royalties_and_fees parity)';

    public function handle(RoyaltyBatchCalculationService $service): int
    {
        if (! config('fil-royalties.enable_royalty_calculation_job')) {
            $this->warn('Royalty calculation job is disabled (FIL_ENABLE_ROYALTY_CALC_JOB).');

            return self::SUCCESS;
        }

        $stats = $service->calculateDueStores();

        $this->info(sprintf(
            'Royalties: %d created, %d skipped, %d processed.',
            $stats['created'],
            $stats['skipped'],
            $stats['processed'],
        ));

        return self::SUCCESS;
    }
}
