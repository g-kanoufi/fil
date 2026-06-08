<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UiAccessSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(UiAccessSeeder::class);
});

test('field schema filters by legacy_post_type', function () {
    $group = FieldGroup::query()->create([
        'key' => 'units',
        'title' => 'Units',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'store',
        'legacy_post_type' => 'store',
        'key' => 'unit_only_field',
        'name' => 'Unit only',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'store',
        'legacy_post_type' => 'franchise_location',
        'key' => 'location_only_field',
        'name' => 'Location only',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 2,
        'status' => 'active',
    ]);

    $user = User::factory()->create();
    $user->assignRole('admin');

    $storeKeys = collect(
        $this->actingAs($user)
            ->getJson('/api/v1/fields?entity=store&legacy_post_type=store')
            ->assertOk()
            ->json('data.groups.0.fields'),
    )->pluck('key');

    expect($storeKeys)->toContain('unit_only_field')
        ->not->toContain('location_only_field');
});
