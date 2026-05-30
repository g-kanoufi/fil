<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(UiAccessSeeder::class);
        $this->call(FieldSchemaSeeder::class);
        $this->call(WidgetFormSeeder::class);

        $admin = User::query()->updateOrCreate(
            ['email' => 'admin@fil.test'],
            [
                'name' => 'FIL Admin',
                'first_name' => 'FIL',
                'last_name' => 'Admin',
                'password' => Hash::make('password'),
            ],
        );
        $admin->syncRoles(['admin']);

        if (app()->environment('local', 'testing')) {
            $this->call(DemoSeeder::class);
            $this->call(NotificationRuleSeeder::class);
        }
    }
}
