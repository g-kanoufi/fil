<?php

declare(strict_types=1);

namespace App\Services\Stores;

use App\Models\Store;
use App\Models\StoreOpeningChecklistItem;

final class StoreOpeningChecklistService
{
    /**
     * @return list<array{key: string, label: string, completed_at: string|null, notes: string|null, sort_order: int}>
     */
    public function forStore(Store $store): array
    {
        $this->ensureDefaultItems($store);

        return StoreOpeningChecklistItem::query()
            ->where('store_id', $store->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (StoreOpeningChecklistItem $item): array => [
                'key' => $item->item_key,
                'label' => $item->label,
                'completed_at' => $item->completed_at?->toIso8601String(),
                'notes' => $item->notes,
                'sort_order' => (int) $item->sort_order,
            ])
            ->all();
    }

    /**
     * @param  list<array{key: string, completed?: bool|null, completed_at?: string|null, notes?: string|null}>  $items
     */
    public function updateItems(Store $store, array $items): array
    {
        $this->ensureDefaultItems($store);

        foreach ($items as $payload) {
            $key = (string) ($payload['key'] ?? '');

            if ($key === '') {
                continue;
            }

            $item = StoreOpeningChecklistItem::query()
                ->where('store_id', $store->id)
                ->where('item_key', $key)
                ->first();

            if ($item === null) {
                continue;
            }

            if (array_key_exists('notes', $payload)) {
                $item->notes = filled($payload['notes']) ? (string) $payload['notes'] : null;
            }

            if (array_key_exists('completed_at', $payload)) {
                $item->completed_at = filled($payload['completed_at']) ? $payload['completed_at'] : null;
            } elseif (array_key_exists('completed', $payload)) {
                $item->completed_at = ($payload['completed'] ?? false) ? now() : null;
            }

            $item->save();
        }

        return $this->forStore($store->fresh() ?? $store);
    }

    public function ensureDefaultItems(Store $store): void
    {
        $existing = StoreOpeningChecklistItem::query()
            ->where('store_id', $store->id)
            ->pluck('item_key')
            ->all();

        /** @var list<string> $defaults */
        $defaults = config('fil-platform.opening.default_checklist_keys', []);
        $labels = config('fil-platform.opening.default_checklist_labels', []);

        foreach ($defaults as $index => $key) {
            if (in_array($key, $existing, true)) {
                continue;
            }

            StoreOpeningChecklistItem::query()->create([
                'store_id' => $store->id,
                'item_key' => $key,
                'label' => (string) ($labels[$key] ?? $this->labelFromKey($key)),
                'sort_order' => ($index + 1) * 10,
            ]);
        }
    }

    private function labelFromKey(string $key): string
    {
        return str($key)->replace('_', ' ')->title()->toString();
    }
}
