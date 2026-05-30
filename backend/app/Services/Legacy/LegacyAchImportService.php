<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\AchTransfer;
use App\Models\Store;
use Carbon\Carbon;

final class LegacyAchImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{transfers: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $stats = ['transfers' => 0, 'skipped' => 0];

        $result = $this->importer->import(
            $dumpPath,
            $prefix.'ach_transfers',
            function (array $row, bool $execute) use (&$stats): void {
                $legacyId = (int) ($row['id'] ?? 0);
                $legacyStoreId = (int) ($row['store_id'] ?? 0);

                if ($legacyId <= 0) {
                    $stats['skipped']++;

                    return;
                }

                $storeId = $legacyStoreId > 0 ? $this->resolveStoreId($legacyStoreId) : null;

                if ($legacyStoreId > 0 && $storeId === null) {
                    $stats['skipped']++;

                    return;
                }

                $stats['transfers']++;

                if (! $execute) {
                    return;
                }

                AchTransfer::query()->updateOrCreate(
                    ['legacy_transfer_id' => $legacyId],
                    [
                        'store_id' => $storeId,
                        'transferred_at' => $this->parseTime($row['time'] ?? null),
                        'source_funding_source_id' => null,
                        'destination_funding_source_id' => null,
                        'external_transfer_id' => $row['transfer'] ?? null,
                        'provider' => 'dwolla',
                        'provider_status' => $row['dwolla_status'] ?? null,
                        'status' => (int) ($row['status'] ?? 0),
                        'amount' => (float) ($row['amount'] ?? 0),
                        'royalty_name' => $row['royalty_name'] ?? null,
                        'description' => $row['description'] ?? null,
                        'addenda' => $row['addenda'] ?? null,
                        'errors' => filled($row['errors'] ?? null) ? (string) $row['errors'] : null,
                        'meta' => [
                            'legacy_source' => $row['source'] ?? null,
                            'legacy_destination' => $row['destination'] ?? null,
                            'legacy_royalty_id' => $row['royalty_id'] ?? null,
                            'legacy_royalties_ids' => $row['royalties_ids'] ?? null,
                            'legacy_store_ids' => $row['store_ids'] ?? null,
                        ],
                    ],
                );
            },
            $execute,
        );

        $stats['skipped'] += $result['skipped'];

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
