<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(DripCampaignSeeder::class);

        $franchisor = $this->demoUser(
            'franchisor@fil.test',
            'Franchisor Demo',
            'Franchisor',
            'Demo',
            'franchisor',
        );

        $leadOwner = $this->demoUser(
            'owner@fil.test',
            'Lead Owner Demo',
            'Lead',
            'Owner',
            'lead_owner',
        );

        $areaRep = $this->demoUser(
            'area_rep@fil.test',
            'Area Rep Demo',
            'Area',
            'Rep',
            'area_rep',
        );

        $franchisee = $this->demoUser(
            'franchisee@fil.test',
            'Franchisee Demo',
            'Franchisee',
            'Demo',
            'franchisee',
        );

        $storeManager = $this->demoUser(
            'storemanager@fil.test',
            'Store Manager Demo',
            'Store',
            'Manager',
            'storemanager',
        );

        $employee = $this->demoUser(
            'employee@fil.test',
            'Employee Demo',
            'Employee',
            'Demo',
            'employee',
        );

        $this->demoUser(
            'prospect@fil.test',
            'Prospect Demo',
            'Prospect',
            'Demo',
            'prospect',
        );

        $area = Area::query()->firstOrCreate(
            ['slug' => 'southwest'],
            [
                'name' => 'Southwest Territory',
                'status' => 'active',
                'extras' => ['rep_user_id' => $areaRep->id],
            ],
        );

        if ((int) data_get($area->extras, 'rep_user_id') !== $areaRep->id) {
            $area->update([
                'extras' => array_merge($area->extras ?? [], ['rep_user_id' => $areaRep->id]),
            ]);
        }

        $scottsdale = Store::query()->firstOrCreate(
            ['slug' => 'primeiv-scottsdale'],
            [
                'name' => 'PrimeIV Scottsdale',
                'area_id' => $area->id,
                'store_status' => 'open',
                'status' => 'active',
                'royalty_config' => ['default_rate' => 0.06],
            ],
        );

        $phoenix = Store::query()->firstOrCreate(
            ['slug' => 'primeiv-phoenix'],
            [
                'name' => 'PrimeIV Phoenix',
                'area_id' => $area->id,
                'store_status' => 'pending',
                'status' => 'active',
            ],
        );

        $this->assignStore($scottsdale->id, $franchisee->id, 'owner');
        $this->assignStore($scottsdale->id, $employee->id, 'employee');
        $this->assignStore($phoenix->id, $storeManager->id, 'manager');

        $leadDefinitions = [
            ['title' => 'Jane Smith Application', 'lead_status' => '1', 'lead_fdd_status' => 'active', 'lead_temp' => 'hot', 'lead_source' => 'widget', 'pipeline_phase' => 1],
            ['title' => 'Robert Chen Application', 'lead_status' => '2', 'lead_fdd_status' => 'active', 'lead_temp' => 'warm', 'lead_source' => 'referral', 'pipeline_phase' => 2],
            ['title' => 'Maria Lopez Application', 'lead_status' => '6', 'lead_fdd_status' => 'disclosed', 'lead_temp' => 'hot', 'lead_source' => 'web', 'pipeline_phase' => 5],
            ['title' => 'David Park Application', 'lead_status' => '9', 'lead_fdd_status' => 'inactive', 'lead_temp' => 'cold', 'lead_source' => 'event', 'pipeline_phase' => 99],
        ];

        foreach ($leadDefinitions as $definition) {
            Lead::query()->firstOrCreate(
                ['title' => $definition['title']],
                [
                    ...$definition,
                    'owner_user_id' => $leadOwner->id,
                    'area_id' => $area->id,
                    'lead_stage' => (string) $definition['pipeline_phase'],
                    'likelihood_to_close' => match ($definition['lead_temp']) {
                        'hot' => 80,
                        'warm' => 50,
                        default => 20,
                    },
                    'status' => 'active',
                ],
            );
        }

        Fdd::query()->firstOrCreate(
            ['slug' => 'primeiv-unit-fdd'],
            [
                'type' => 'unit',
                'title' => 'PrimeIV Unit FDD',
                'status' => 'active',
                'area_id' => $area->id,
            ],
        );

        Fdd::query()->firstOrCreate(
            ['slug' => 'primeiv-area-fdd'],
            [
                'type' => 'area',
                'title' => 'PrimeIV Area FDD',
                'status' => 'active',
                'area_id' => $area->id,
            ],
        );

        unset($franchisor);
    }

    private function demoUser(
        string $email,
        string $name,
        string $firstName,
        string $lastName,
        string $role,
    ): User {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'password' => Hash::make('password'),
            ],
        );

        $user->forceFill([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => $lastName,
            'password' => Hash::make('password'),
        ])->save();

        $user->syncRoles([$role]);

        return $user;
    }

    private function assignStore(int $storeId, int $userId, string $role): void
    {
        StoreOwner::query()->firstOrCreate(
            [
                'store_id' => $storeId,
                'user_id' => $userId,
            ],
            [
                'ownership_pct' => $role === 'owner' ? 100 : null,
                'role' => $role,
            ],
        );
    }
}
