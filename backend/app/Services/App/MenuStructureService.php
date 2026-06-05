<?php

declare(strict_types=1);

namespace App\Services\App;

use App\Models\Area;
use App\Models\Store;
use App\Models\UiMenuItem;
use App\Models\User;
use App\Services\Leads\LeadStatusMenuService;
use App\Services\Stores\StoreStatusMenuService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final class MenuStructureService
{
    public function __construct(
        private readonly LeadStatusMenuService $leadStatusMenus,
        private readonly StoreStatusMenuService $storeStatusMenus,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function forStaffApp(): array
    {
        return [
            'top_menus' => $this->topMenus(),
            'menus_with_columns' => [
                'leads' => $this->entityMenu('grid_leads'),
                'stores' => $this->entityMenu('grid_stores'),
                'contacts' => $this->entityMenu('grid_contacts'),
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function topMenus(): array
    {
        return collect(config('fil.navigation', []))
            ->filter(fn (array $item): bool => ($item['type'] ?? null) !== 'section' && isset($item['id'], $item['path']))
            ->map(fn (array $item): array => [
                'name' => $item['id'],
                'label' => $item['label'],
                'value' => $item['label'],
                'url' => $item['path'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    private function entityMenu(string $domain): array
    {
        $items = UiMenuItem::query()
            ->where('domain', $domain)
            ->whereIn('item_type', ['menu', 'submenu'])
            ->orderBy('sort_order')
            ->get();

        $menuItems = [];
        $subMenuItems = [];

        foreach ($items as $item) {
            if ($item->item_type === 'menu') {
                $menuItems[$item->key] = ['label' => $item->label, 'slug' => $item->key];

                continue;
            }

            $parent = $item->parent_key ?? '_root';
            $subMenuItems[$parent][$item->key] = ['label' => $item->label, 'slug' => $item->key];
        }

        return match ($domain) {
            'grid_leads' => $this->enrichLeadMenus([
                'menuItems' => $menuItems,
                'subMenuItems' => $subMenuItems,
            ]),
            'grid_stores' => $this->storeStatusMenus->enrichStoreMenus($this->enrichStoreAreaMenus([
                'menuItems' => $menuItems,
                'subMenuItems' => $subMenuItems,
            ])),
            'grid_contacts' => $this->enrichContactMenus([
                'menuItems' => $menuItems,
                'subMenuItems' => $subMenuItems,
            ]),
            default => [
                'menuItems' => $menuItems,
                'subMenuItems' => $subMenuItems,
            ],
        };
    }

    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    private function enrichLeadMenus(array $menu): array
    {
        return $this->leadStatusMenus->enrichLeadMenus($menu);
    }

    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    private function enrichStoreAreaMenus(array $menu): array
    {
        if (isset($menu['menuItems']['store_area'])) {
            $storeAreaIds = Store::query()
                ->whereNotNull('area_id')
                ->distinct()
                ->pluck('area_id');

            $areas = Area::query()
                ->where(function ($query) use ($storeAreaIds): void {
                    $query->where('status', 'active');

                    if ($storeAreaIds->isNotEmpty()) {
                        $query->orWhereIn('id', $storeAreaIds);
                    }
                })
                ->orderBy('name')
                ->get(['id', 'name']);

            foreach ($areas as $area) {
                $slug = (string) $area->id;
                $menu['subMenuItems']['store_area'][$slug] = [
                    'label' => (string) $area->name,
                    'slug' => $slug,
                ];
            }
        }

        return $menu;
    }

    /**
     * @param  array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}  $menu
     * @return array{menuItems: array<string, array{label: string|null, slug: string}>, subMenuItems: array<string, array<string, array{label: string|null, slug: string}>>}
     */
    private function enrichContactMenus(array $menu): array
    {
        if (! isset($menu['menuItems']['contact_role'])) {
            return $menu;
        }

        $roles = DB::table('roles')
            ->join('model_has_roles', 'roles.id', '=', 'model_has_roles.role_id')
            ->join('users', 'users.id', '=', 'model_has_roles.model_id')
            ->where('model_has_roles.model_type', User::class)
            ->where('roles.name', '!=', 'prospect')
            ->distinct()
            ->orderBy('roles.name')
            ->pluck('roles.name');

        foreach ($roles as $role) {
            $slug = $this->slugForRole((string) $role);
            $menu['subMenuItems']['contact_role'][$slug] = [
                'label' => Str::headline(str_replace('_', ' ', (string) $role)),
                'slug' => $slug,
            ];
        }

        return $menu;
    }

    private function slugForRole(string $role): string
    {
        $normalized = preg_replace('/[-\s]+/', '_', trim($role)) ?? $role;

        return strtolower($normalized);
    }
}
