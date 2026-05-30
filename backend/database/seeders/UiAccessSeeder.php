<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\ClientSetting;
use App\Models\RoleNoteGrant;
use App\Models\RoleUiGrant;
use App\Models\UiMenuItem;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

final class UiAccessSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedCatalog();
        $this->seedGrants();
        $this->seedNoteGrants();
        $this->seedClientSettings();
    }

    private function seedCatalog(): void
    {
        /** @var array<string, list<array<string, mixed>>> $domains */
        $domains = config('fil-ui-catalog.domains', []);
        $sort = 0;

        foreach ($domains as $domain => $items) {
            foreach ($items as $item) {
                UiMenuItem::query()->updateOrCreate(
                    ['domain' => $domain, 'key' => $item['key']],
                    [
                        'label' => $item['label'] ?? $item['key'],
                        'parent_key' => $item['parent_key'] ?? null,
                        'item_type' => $item['item_type'],
                        'sort_order' => $sort++,
                    ],
                );
            }
        }
    }

    private function seedGrants(): void
    {
        $roles = Role::query()->pluck('name')->all();
        $items = UiMenuItem::all()->keyBy(fn (UiMenuItem $item) => $item->domain.'.'.$item->key);

        /** @var array<string, array<string, list<string>>> $denied */
        $denied = config('fil-ui-catalog.default_denied', []);

        foreach ($roles as $role) {
            if ($role === 'admin') {
                foreach ($items as $item) {
                    RoleUiGrant::query()->updateOrCreate(
                        ['role' => $role, 'ui_menu_item_id' => $item->id],
                        ['allowed' => true],
                    );
                }

                continue;
            }

            if ($role === 'prospect') {
                continue;
            }

            $roleDenied = $denied[$role] ?? [];

            foreach ($items as $item) {
                $denyList = $roleDenied[$item->domain] ?? [];
                $allowed = ! in_array($item->key, $denyList, true);

                RoleUiGrant::query()->updateOrCreate(
                    ['role' => $role, 'ui_menu_item_id' => $item->id],
                    ['allowed' => $allowed],
                );
            }
        }

        // Franchisor: explicit allow-all if not in default_denied with entries
        if (Role::query()->where('name', 'franchisor')->exists()) {
            foreach ($items as $item) {
                RoleUiGrant::query()->updateOrCreate(
                    ['role' => 'franchisor', 'ui_menu_item_id' => $item->id],
                    ['allowed' => true],
                );
            }
        }
    }

    private function seedNoteGrants(): void
    {
        /** @var array<string, array<string, array{0: bool, 1: bool}>> $defaults */
        $defaults = config('fil-ui-catalog.default_note_grants', []);

        foreach ($defaults as $role => $entities) {
            foreach ($entities as $entity => [$notes, $private]) {
                RoleNoteGrant::query()->updateOrCreate(
                    ['role' => $role, 'entity' => $entity],
                    [
                        'can_view_notes' => $notes,
                        'can_view_private_notes' => $private,
                    ],
                );
            }
        }
    }

    private function seedClientSettings(): void
    {
        $defaults = [
            'brandName' => 'FIL',
            'topBarColor' => '#53387b',
            'highlightColor' => '#53387b',
            'linkColor' => '#53387b',
            'enable_zai' => true,
        ];

        foreach ($defaults as $key => $value) {
            ClientSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
