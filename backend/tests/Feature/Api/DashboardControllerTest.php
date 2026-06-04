<?php

use App\Models\Lead;
use App\Models\Store;
use App\Models\StoreOwner;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('franchisor can load dashboard stats', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisor');

    Lead::factory()->count(2)->create();

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.leads.total', 2)
        ->assertJsonStructure([
            'data' => [
                'leads' => ['total', 'by_status', 'added_monthly'],
                'stores' => ['total'],
                'fdd_deliveries' => ['sent_30d', 'total', 'sent_monthly'],
                'recent_leads',
                'pipeline',
                'chart_months',
            ],
        ]);
});

test('franchisee can load scoped dashboard stats', function () {
    $user = User::factory()->create();
    $user->assignRole('franchisee');

    $visible = Store::factory()->create(['name' => 'Visible store', 'status' => 'active']);
    Store::factory()->create(['name' => 'Hidden store', 'status' => 'active']);
    Lead::factory()->count(3)->create(['status' => 'active']);

    StoreOwner::query()->create([
        'store_id' => $visible->id,
        'user_id' => $user->id,
        'role' => 'owner',
    ]);

    $this->actingAs($user)
        ->getJson('/api/v1/dashboard')
        ->assertOk()
        ->assertJsonPath('data.leads.total', 0)
        ->assertJsonPath('data.stores.total', 1)
        ->assertJsonStructure([
            'data' => [
                'store_ops' => [
                    'inspection_due_count',
                    'checklist_incomplete_count',
                    'stores',
                ],
            ],
        ]);
});
