<?php

declare(strict_types=1);

namespace App\Services\Ach;

use App\Models\AchTransfer;
use App\Models\RoyaltyLineItem;
use App\Models\User;
use App\Services\Auth\ResourceScopeService;
use Illuminate\Database\Eloquent\Builder;

final class AchReconciliationService
{
    public function __construct(
        private readonly ResourceScopeService $scope,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function summary(User $user): array
    {
        $lineItems = RoyaltyLineItem::query()->where('royalty_type', 'unit');
        $this->applyLineItemScope($lineItems, $user);

        $transfers = AchTransfer::query();
        $this->scope->applyAchTransferScope($transfers, $user);

        $unpaidQuery = (clone $lineItems)->where('payment_status', 0);
        $paidQuery = (clone $lineItems)->where('payment_status', '!=', 0);

        $failedTransfers = (clone $transfers)
            ->where(function (Builder $query): void {
                $query->where('status', 0)
                    ->orWhereIn('provider_status', ['failed', 'cancelled']);
            });

        $linkedTransferIds = (clone $paidQuery)
            ->whereNotNull('ach_transfer_id')
            ->pluck('ach_transfer_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $orphanTransfers = (clone $transfers)
            ->when($linkedTransferIds !== [], fn (Builder $query) => $query->whereNotIn('id', $linkedTransferIds))
            ->when($linkedTransferIds === [], fn (Builder $query) => $query);

        return [
            'unpaid_line_items' => [
                'count' => (clone $unpaidQuery)->count(),
                'total_amount' => (string) ((clone $unpaidQuery)->sum('royalty_amount') ?: '0.00'),
            ],
            'paid_line_items' => [
                'count' => (clone $paidQuery)->count(),
                'total_amount' => (string) ((clone $paidQuery)->sum('royalty_amount') ?: '0.00'),
            ],
            'failed_transfers' => [
                'count' => (clone $failedTransfers)->count(),
                'total_amount' => (string) ((clone $failedTransfers)->sum('amount') ?: '0.00'),
            ],
            'orphan_transfers' => [
                'count' => (clone $orphanTransfers)->count(),
                'total_amount' => (string) ((clone $orphanTransfers)->sum('amount') ?: '0.00'),
            ],
        ];
    }

    /**
     * @param  Builder<RoyaltyLineItem>  $query
     */
    private function applyLineItemScope(Builder $query, User $user): void
    {
        if ($this->scope->isUnrestricted($user)) {
            return;
        }

        $storeIds = \App\Models\Store::query()
            ->tap(fn (Builder $storeQuery) => $this->scope->applyStoreScope($storeQuery, $user))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($storeIds === []) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereIn('store_id', $storeIds);
    }
}
