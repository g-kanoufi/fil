<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\RoyaltyLineItem;
use App\Models\RoyaltyPeriod;
use App\Models\Store;
use Carbon\Carbon;

final class LegacyRoyaltyImportService
{
    /** @var array<int, int> */
    private array $periodIdMap = [];

    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{periods: int, line_items: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $stats = ['periods' => 0, 'line_items' => 0, 'skipped' => 0];

        $periodResult = $this->importer->import(
            $dumpPath,
            $prefix.'weekly_store_revenue',
            function (array $row, bool $execute) use (&$stats): void {
                $legacyId = (int) ($row['id'] ?? 0);
                $legacyStoreId = (int) ($row['store_id'] ?? 0);

                if ($legacyId <= 0 || $legacyStoreId <= 0) {
                    $stats['skipped']++;

                    return;
                }

                $storeId = $this->resolveStoreId($legacyStoreId);

                if ($storeId === null) {
                    $stats['skipped']++;

                    return;
                }

                $stats['periods']++;

                if (! $execute) {
                    return;
                }

                $period = RoyaltyPeriod::query()->updateOrCreate(
                    ['legacy_period_id' => $legacyId],
                    [
                        'store_id' => $storeId,
                        'frequency' => $row['frequency'] ?? 'weekly',
                        'period_start' => $this->parseTime($row['week_start'] ?? null),
                        'period_end' => $this->parseTime($row['week_end'] ?? null),
                        'recorded_at' => $this->parseTime($row['time'] ?? null),
                        'gross_revenue' => $row['week_revenue'] ?? null,
                        'order_count' => (int) ($row['orders'] ?? 0),
                        'total_royalties' => $row['royalties'] ?? null,
                        'status' => 'imported',
                    ],
                );

                $this->periodIdMap[$legacyId] = $period->id;
            },
            $execute,
        );

        $stats['skipped'] += $periodResult['skipped'];

        $lineResult = $this->importer->import(
            $dumpPath,
            $prefix.'weekly_store_royalties_detail',
            function (array $row, bool $execute) use (&$stats): void {
                $legacyId = (int) ($row['id'] ?? 0);
                $legacyStoreId = (int) ($row['store_id'] ?? 0);
                $legacyPeriodId = (int) ($row['weekly_store_revenue_id'] ?? 0);

                if ($legacyId <= 0 || $legacyStoreId <= 0) {
                    $stats['skipped']++;

                    return;
                }

                $storeId = $this->resolveStoreId($legacyStoreId);
                $periodId = $this->periodIdMap[$legacyPeriodId]
                    ?? RoyaltyPeriod::query()->where('legacy_period_id', $legacyPeriodId)->value('id');

                if ($storeId === null || $periodId === null) {
                    $stats['skipped']++;

                    return;
                }

                $stats['line_items']++;

                if (! $execute) {
                    return;
                }

                RoyaltyLineItem::query()->updateOrCreate(
                    ['legacy_line_item_id' => $legacyId],
                    [
                        'royalty_period_id' => $periodId,
                        'store_id' => $storeId,
                        'frequency' => $row['frequency'] ?? 'weekly',
                        'trigger_day' => $row['trigger_day'] ?? 'monday',
                        'royalty_type' => (string) ($row['royalty_type'] ?? 'unit'),
                        'royalty_name' => (string) ($row['royalty_name'] ?? 'Royalty'),
                        'gross_revenue' => $row['week_revenue'] ?? null,
                        'royalty_rate' => (float) ($row['royalty_rate'] ?? 0),
                        'royalty_amount' => $row['royalty'] ?? null,
                        'ach_source' => $row['ach_source'] ?? null,
                        'ach_destination' => $row['ach_destination'] ?? null,
                        'funding_source' => $row['funding_source'] ?? null,
                        'payment_status' => (int) ($row['status'] ?? 0),
                        'external_transfer_id' => $row['transfer_id'] ?? null,
                    ],
                );
            },
            $execute,
        );

        $stats['skipped'] += $lineResult['skipped'];

        return $stats;
    }

    private function resolveStoreId(int $legacyStorePostId): ?int
    {
        $id = Store::query()->where('legacy_post_id', $legacyStorePostId)->value('id');

        return $id !== null ? (int) $id : null;
    }

    private function parseTime(?string $value): Carbon
    {
        if ($value === null || trim($value) === '') {
            return now();
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return now();
        }
    }
}
