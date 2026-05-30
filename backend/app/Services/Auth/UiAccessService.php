<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\ClientSetting;
use App\Models\RoleNoteGrant;
use App\Models\RoleUiGrant;
use App\Models\UiMenuItem;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Builds UI restriction payloads from normalized DB tables.
 *
 * Legacy shape (disabled lists) is kept for Sidebar / GridToolbar port parity.
 */
final class UiAccessService
{
    /**
     * @return array<string, mixed>
     */
    public function restrictionsFor(User $user): array
    {
        $roles = $user->getRoleNames()->all();

        if ($roles === []) {
            return $this->emptyRestrictions();
        }

        $items = UiMenuItem::query()->orderBy('domain')->orderBy('sort_order')->get();
        $grants = $this->resolveGrants($roles, $items);

        return [
            'tabs' => $this->disabledKeys($items, $grants, 'grid_tabs'),
            'leads' => $this->disabledMenuShape($items, $grants, 'grid_leads'),
            'stores' => $this->disabledMenuShape($items, $grants, 'grid_stores'),
            'contacts' => $this->disabledMenuShape($items, $grants, 'grid_contacts'),
            'nav_admin' => $this->disabledMenuShape($items, $grants, 'nav_admin'),
            'nav_stores' => $this->disabledMenuShape($items, $grants, 'nav_stores'),
            'nav_contacts' => $this->disabledMenuShape($items, $grants, 'nav_contacts'),
            'resources' => [],
            'allowed_zai_resources' => [],
            'features' => $this->allowedFeatureMap($items, $grants),
        ];
    }

    /**
     * @return array<string, array{notes: bool, private_notes: bool}>
     */
    public function notesAccessFor(User $user): array
    {
        $roles = $user->getRoleNames()->all();
        $entities = config('fil-ui-catalog.note_entities', ['lead', 'store', 'area', 'contact']);
        $legacyMap = [
            'lead' => 'application',
            'store' => 'store',
            'area' => 'franchise_location',
            'contact' => 'user',
        ];

        $result = [];

        foreach ($entities as $entity) {
            $result[$legacyMap[$entity] ?? $entity] = [
                'notes' => $this->roleNoteAllowed($roles, $entity, 'can_view_notes'),
                'private_notes' => $this->roleNoteAllowed($roles, $entity, 'can_view_private_notes'),
            ];
        }

        return $result;
    }

    /**
     * @param  list<string>  $roles
     * @param  Collection<int, UiMenuItem>  $items
     * @return array<int, bool> keyed by ui_menu_item id
     */
    private function resolveGrants(array $roles, Collection $items): array
    {
        $grantRows = RoleUiGrant::query()
            ->whereIn('role', $roles)
            ->get()
            ->groupBy('ui_menu_item_id');

        $resolved = [];

        foreach ($items as $item) {
            $rows = $grantRows->get($item->id, collect());
            // Permissive union: allowed if any role allows.
            $resolved[$item->id] = $rows->contains(fn (RoleUiGrant $grant): bool => $grant->allowed);
        }

        return $resolved;
    }

    /**
     * @param  Collection<int, UiMenuItem>  $items
     * @param  array<int, bool>  $grants
     * @return list<string>
     */
    private function disabledKeys(Collection $items, array $grants, string $domain): array
    {
        return $items
            ->where('domain', $domain)
            ->filter(fn (UiMenuItem $item): bool => ! ($grants[$item->id] ?? true))
            ->pluck('key')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, UiMenuItem>  $items
     * @param  array<int, bool>  $grants
     * @return array{menuItems: list<string>, subMenuItems: list<string>}
     */
    private function disabledMenuShape(Collection $items, array $grants, string $domain): array
    {
        $domainItems = $items->where('domain', $domain);

        $menuItems = $domainItems
            ->where('item_type', 'menu')
            ->filter(fn (UiMenuItem $item): bool => ! ($grants[$item->id] ?? true))
            ->pluck('key')
            ->values()
            ->all();

        $subMenuItems = $domainItems
            ->where('item_type', 'submenu')
            ->filter(fn (UiMenuItem $item): bool => ! ($grants[$item->id] ?? true))
            ->pluck('key')
            ->values()
            ->all();

        $navItems = $domainItems
            ->where('item_type', 'nav')
            ->filter(fn (UiMenuItem $item): bool => ! ($grants[$item->id] ?? true))
            ->pluck('key')
            ->values()
            ->all();

        if ($navItems !== []) {
            return [
                'menuItems' => $navItems,
                'subMenuItems' => [],
            ];
        }

        return [
            'menuItems' => $menuItems,
            'subMenuItems' => $subMenuItems,
        ];
    }

    /**
     * @param  Collection<int, UiMenuItem>  $items
     * @param  array<int, bool>  $grants
     * @return array<string, bool>
     */
    private function allowedFeatureMap(Collection $items, array $grants): array
    {
        return $items
            ->where('domain', 'features')
            ->mapWithKeys(fn (UiMenuItem $item): array => [
                $item->key => $grants[$item->id] ?? true,
            ])
            ->all();
    }

    /**
     * @param  list<string>  $roles
     */
    private function roleNoteAllowed(array $roles, string $entity, string $column): bool
    {
        return RoleNoteGrant::query()
            ->whereIn('role', $roles)
            ->where('entity', $entity)
            ->where($column, true)
            ->exists();
    }

    /**
     * @return array<string, mixed>
     */
    private function emptyRestrictions(): array
    {
        return [
            'tabs' => [],
            'leads' => ['menuItems' => [], 'subMenuItems' => []],
            'stores' => ['menuItems' => [], 'subMenuItems' => []],
            'contacts' => ['menuItems' => [], 'subMenuItems' => []],
            'nav_admin' => ['menuItems' => [], 'subMenuItems' => []],
            'nav_stores' => ['menuItems' => [], 'subMenuItems' => []],
            'nav_contacts' => ['menuItems' => [], 'subMenuItems' => []],
            'resources' => [],
            'allowed_zai_resources' => [],
            'features' => [],
        ];
    }
}
