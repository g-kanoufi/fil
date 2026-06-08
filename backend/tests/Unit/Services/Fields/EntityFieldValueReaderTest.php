<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldValue;
use App\Models\Store;
use App\Services\Fields\EntityFieldValueReader;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('scopes custom values by legacy post type on store entity', function () {
    $store = Store::factory()->create();

    $storeGroup = FieldGroup::query()->create([
        'key' => 'units',
        'title' => 'Units',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $locationGroup = FieldGroup::query()->create([
        'key' => 'locations',
        'title' => 'Locations',
        'sort_order' => 2,
        'status' => 'active',
    ]);

    $storeField = Field::query()->create([
        'field_group_id' => $storeGroup->id,
        'entity' => 'store',
        'key' => 'spa_id_custom',
        'name' => 'Spa ID custom',
        'legacy_post_type' => 'store',
        'storage' => 'field_value',
        'type' => 'text',
        'status' => 'active',
        'sort_order' => 1,
    ]);

    $locationField = Field::query()->create([
        'field_group_id' => $locationGroup->id,
        'entity' => 'store',
        'key' => 'lease_status',
        'name' => 'Lease status',
        'legacy_post_type' => 'franchise_location',
        'storage' => 'field_value',
        'type' => 'text',
        'status' => 'active',
        'sort_order' => 1,
    ]);

    FieldValue::query()->create([
        'field_id' => $storeField->id,
        'entity_type' => 'store',
        'entity_id' => $store->id,
        'value_text' => 'store-only',
    ]);

    FieldValue::query()->create([
        'field_id' => $locationField->id,
        'entity_type' => 'store',
        'entity_id' => $store->id,
        'value_text' => 'location-only',
    ]);

    $reader = app(EntityFieldValueReader::class);

    expect($reader->forEntity('store', $store->id, 'store'))
        ->toBe(['spa_id_custom' => 'store-only']);

    expect($reader->forEntity('store', $store->id, 'franchise_location'))
        ->toBe(['lease_status' => 'location-only']);
});
