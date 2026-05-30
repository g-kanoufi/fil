<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Royalties\AreaRoyaltyCalculationService;
use Illuminate\Console\Command;

final class CalculateAreaRoyaltiesCommand extends Command
{
    protected $signature = 'areas:calculate-royalties';

    protected $description = 'Aggregate area-level royalties on the configured day of month (legacy save_areas_royalties parity)';

    public function handle(AreaRoyaltyCalculationService $service): int
    {
        $stats = $service->saveDueAreaRoyalties();

        if ($stats['processed'] === 0 && $stats['created'] === 0 && $stats['skipped'] === 0) {
            $day = config('fil-royalties.area_royalties_calc_day');
            $this->line("Not the area royalty calc day (configured: day {$day} of month).");

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Area royalties: %d created, %d skipped, %d processed.',
            $stats['created'],
            $stats['skipped'],
            $stats['processed'],
        ));

        return self::SUCCESS;
    }
}
