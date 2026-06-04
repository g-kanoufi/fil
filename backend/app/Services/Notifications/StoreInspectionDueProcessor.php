<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\NotificationRule;
use App\Models\Store;
use Illuminate\Support\Collection;

final class StoreInspectionDueProcessor
{
    public function __construct(
        private readonly NotificationDispatcher $dispatcher,
    ) {}

    public function process(int $limit = 200): int
    {
        $lookaheadDays = (int) config('fil-platform.operate_inspect.inspection_lookahead_days', 14);
        $lookbackDays = (int) config('fil-platform.operate_inspect.inspection_lookback_days', 7);
        $trigger = 'store.inspection_due';

        $windowStart = now()->subDays($lookbackDays)->toDateString();
        $windowEnd = now()->addDays($lookaheadDays)->toDateString();

        /** @var Collection<int, Store> $stores */
        $stores = Store::query()
            ->where('status', 'active')
            ->whereNotNull('next_inspection_at')
            ->whereBetween('next_inspection_at', [$windowStart, $windowEnd])
            ->orderBy('next_inspection_at')
            ->limit($limit)
            ->get();

        $dispatched = 0;

        foreach ($stores as $store) {
            if ($this->shouldSkipDuplicate($store, $trigger)) {
                continue;
            }

            $this->dispatcher->dispatchStore($trigger, $store, [
                'scheduled' => true,
                'inspection_due_at' => $store->next_inspection_at?->toDateString(),
            ]);

            $dispatched++;
        }

        return $dispatched;
    }

    private function shouldSkipDuplicate(Store $store, string $trigger): bool
    {
        return NotificationRule::query()
            ->where('enabled', true)
            ->where(function ($query) use ($trigger): void {
                $query->where('trigger_slug', $trigger);

                /** @var array<string, string> $legacyMap */
                $legacyMap = config('fil-notifications.legacy_trigger_map', []);
                $legacySlugs = array_keys(array_filter($legacyMap, fn (string $mapped): bool => $mapped === $trigger));

                if ($legacySlugs !== []) {
                    $query->orWhereIn('trigger_slug', $legacySlugs);
                }
            })
            ->get()
            ->contains(fn (NotificationRule $rule): bool => $rule->shouldSkipDuplicateSendForStore($store));
    }
}
