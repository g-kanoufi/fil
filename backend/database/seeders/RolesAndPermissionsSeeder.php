<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        /** @var list<string> $permissions */
        $permissions = config('fil.permissions', []);

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        /** @var array<string, list<string>|string> $rolePermissions */
        $rolePermissions = config('fil.role_permissions', []);

        foreach ($rolePermissions as $roleName => $grants) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);

            if ($grants === '*') {
                $role->syncPermissions(Permission::all());

                continue;
            }

            $role->syncPermissions($grants);
        }
    }
}
