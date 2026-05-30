<?php

declare(strict_types=1);

namespace App\Services\Legacy;

use App\Models\AchCustomer;
use App\Models\AchFundingSource;
use App\Models\Store;

final class LegacyAchEnrollmentImportService
{
    public function __construct(
        private readonly LegacyNamedTableImporter $importer,
    ) {}

    /**
     * @return array{customers: int, funding_sources: int, skipped: int}
     */
    public function import(string $dumpPath, string $prefix, bool $execute): array
    {
        $stats = ['customers' => 0, 'funding_sources' => 0, 'skipped' => 0];

        /** @var array<int, string|null> $customersByStoreLegacyId */
        $customersByStoreLegacyId = [];

        $this->importer->import(
            $dumpPath,
            $prefix.'postmeta',
            function (array $row, bool $execute) use (&$stats, &$customersByStoreLegacyId): void {
                $metaKey = (string) ($row['meta_key'] ?? '');
                $metaValue = trim((string) ($row['meta_value'] ?? ''));
                $legacyPostId = (int) ($row['post_id'] ?? 0);

                if ($legacyPostId <= 0 || $metaValue === '') {
                    return;
                }

                if ($metaKey === 'dwolla_customer') {
                    $storeId = Store::query()->where('legacy_post_id', $legacyPostId)->value('id');

                    if ($storeId === null) {
                        $stats['skipped']++;

                        return;
                    }

                    $stats['customers']++;
                    $customersByStoreLegacyId[$legacyPostId] = $metaValue;

                    if (! $execute) {
                        return;
                    }

                    AchCustomer::query()->updateOrCreate(
                        [
                            'owner_type' => Store::class,
                            'owner_id' => $storeId,
                            'provider' => 'dwolla',
                        ],
                        [
                            'external_customer_id' => $metaValue,
                            'status' => 'active',
                        ],
                    );
                }

                if ($metaKey === 'dwolla_funding_source') {
                    $storeId = Store::query()->where('legacy_post_id', $legacyPostId)->value('id');

                    if ($storeId === null) {
                        $stats['skipped']++;

                        return;
                    }

                    $stats['funding_sources']++;

                    if (! $execute) {
                        return;
                    }

                    $customer = AchCustomer::query()->firstOrCreate(
                        [
                            'owner_type' => Store::class,
                            'owner_id' => $storeId,
                            'provider' => 'dwolla',
                        ],
                        [
                            'external_customer_id' => $customersByStoreLegacyId[$legacyPostId] ?? 'legacy-unknown',
                            'status' => 'active',
                        ],
                    );

                    AchFundingSource::query()->updateOrCreate(
                        [
                            'ach_customer_id' => $customer->id,
                            'external_funding_source_id' => $metaValue,
                        ],
                        [
                            'name' => 'Primary funding source',
                            'type' => 'bank',
                            'status' => 'active',
                            'is_default' => true,
                        ],
                    );
                }
            },
            $execute,
        );

        return $stats;
    }
}
