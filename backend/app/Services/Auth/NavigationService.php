<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class NavigationService
{
    /**
     * @return list<array<string, mixed>>
     */
    public function forUser(User $user): array
    {
        /** @var list<array<string, mixed>> $items */
        $items = config('fil.navigation', []);

        return Collection::make($items)
            ->map(fn (array $item): ?array => $this->mapNavItem($user, $item))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>|null
     */
    private function mapNavItem(User $user, array $item): ?array
    {
        if (($item['type'] ?? null) === 'section') {
            return [
                'type' => 'section',
                'label' => (string) $item['label'],
            ];
        }

        if (! $this->canSeeNavItem($user, $item)) {
            return null;
        }

        $mapped = [
            'id' => (string) $item['id'],
            'label' => (string) $item['label'],
            'path' => (string) $item['path'],
        ];

        if (isset($item['children']) && is_array($item['children'])) {
            /** @var list<array<string, mixed>> $children */
            $children = $item['children'];

            $mapped['children'] = Collection::make($children)
                ->map(fn (array $child): ?array => $this->mapNavItem($user, $child))
                ->filter()
                ->values()
                ->all();

            if ($mapped['children'] === []) {
                return null;
            }
        }

        return $mapped;
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function canSeeNavItem(User $user, array $item): bool
    {
        $gate = Gate::forUser($user);

        if (isset($item['gate'])) {
            return $gate->allows((string) $item['gate']);
        }

        if (isset($item['policy'], $item['ability'])) {
            return $gate->allows((string) $item['ability'], $item['policy']);
        }

        if (isset($item['permission'])) {
            return $user->can((string) $item['permission']);
        }

        return false;
    }
}
