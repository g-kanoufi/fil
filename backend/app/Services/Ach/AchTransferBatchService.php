<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Actions\Royalties\TriggerAchTransfer;
use App\Models\RoyaltyLineItem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class AchTransferBatchService
{
    public function __construct(
        private readonly TriggerAchTransfer $triggerAch,
    ) {}

    /**
     * @return array{processed: int, skipped: int, transfers: int}
     */
    public function processDueTransfers(): array
    {
        $stats = ['processed' => 0, 'skipped' => 0, 'transfers' => 0];

        RoyaltyLineItem::query()
            ->with(['store', 'period'])
            ->where('payment_status', 0)
            ->where('royalty_amount', '>', 0)
            ->orderBy('id')
            ->chunkById(50, function ($items) use (&$stats): void {
                foreach ($items as $item) {
                    $stats['processed']++;

                    $store = $item->store;
                    $period = $item->period;

                    if ($store === null || $period === null) {
                        $stats['skipped']++;

                        continue;
                    }

                    if ($item->ach_transfer_id !== null) {
                        $stats['skipped']++;

                        continue;
                    }

                    DB::transaction(function () use ($item, $store, $period, &$stats): void {
                        $transfer = $this->triggerAch->handle(
                            $store,
                            $period,
                            (float) $item->royalty_amount,
                        );

                        if (! TriggerAchTransfer::isPaidProviderStatus($transfer->provider_status)) {
                            return;
                        }

                        $item->update([
                            'payment_status' => 1,
                            'ach_transfer_id' => $transfer->id,
                        ]);

                        $stats['transfers']++;
                    });
                }
            });

        Log::info('ACH batch processing finished', $stats);

        return $stats;
    }
}
