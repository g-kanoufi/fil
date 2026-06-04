<?php

declare(strict_types=1);

namespace App\Services\Stores;

use App\Models\Store;
use App\Support\Fields\FieldChoiceCatalog;
use App\Support\Fields\FieldChoiceSet;

final class StoreStatusMenuService
{
    public function __construct(
        private readonly FieldChoiceCatalog $choices,
    ) {}

    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    public function enrichStoreMenus(array $menu): array
    {
        if (! isset($menu['menuItems']['store_status'])) {
            return $menu;
        }

        $sub = [];

        foreach ($this->choiceSet()->all() as $choice) {
            $value = (string) ($choice['value'] ?? '');
            if ($value === '') {
                continue;
            }

            $slug = $this->slugForValue($value);
            $sub[$slug] = [
                'label' => (string) ($choice['label'] ?? $value),
                'slug' => $slug,
            ];
        }

        foreach ($this->distinctStoredStatuses() as $stored) {
            $slug = $this->slugForValue($stored);
            if (isset($sub[$slug])) {
                continue;
            }

            $sub[$slug] = [
                'label' => $stored,
                'slug' => $slug,
            ];
        }

        $menu['subMenuItems']['store_status'] = $sub;

        return $menu;
    }

    private function choiceSet(): FieldChoiceSet
    {
        $set = $this->choices->choicesFor('store', 'store_status');

        if (! $set->isEmpty()) {
            return $set;
        }

        /** @var list<array<string, mixed>> $legacy */
        $legacy = config('fil-fields.system_field_definitions.store.store_status.choices', []);

        return new FieldChoiceSet($legacy);
    }

    /**
     * @return list<string>
     */
    private function distinctStoredStatuses(): array
    {
        return Store::query()
            ->whereNotNull('store_status')
            ->where('store_status', '!=', '')
            ->distinct()
            ->orderBy('store_status')
            ->pluck('store_status')
            ->map(fn (mixed $status): string => (string) $status)
            ->all();
    }

    private function slugForValue(string $value): string
    {
        $normalized = preg_replace('/[\s\/]+/', '_', trim($value)) ?? $value;

        return strtolower($normalized);
    }
}
