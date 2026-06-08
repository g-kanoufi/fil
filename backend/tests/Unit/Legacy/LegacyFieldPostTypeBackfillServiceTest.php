<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Services\Legacy\LegacyFieldPostTypeBackfillService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('backfill sets legacy_post_type from field group config mapping', function () {
    $units = FieldGroup::query()->create([
        'key' => 'units',
        'title' => 'Units',
        'legacy_group_key' => 'group_5e55f6ed3094a',
        'sort_order' => 10,
        'status' => 'active',
    ]);

    $locations = FieldGroup::query()->create([
        'key' => 'locations',
        'title' => 'Locations',
        'legacy_group_key' => 'group_570fc6f67d6f6',
        'sort_order' => 11,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $units->id,
        'entity' => 'store',
        'key' => 'spa_id',
        'name' => 'Spa ID',
        'type' => 'text',
        'legacy_post_type' => '',
        'storage' => 'field_value',
        'status' => 'active',
        'sort_order' => 1,
    ]);

    Field::query()->create([
        'field_group_id' => $locations->id,
        'entity' => 'store',
        'key' => 'lease_status',
        'name' => 'Lease status',
        'type' => 'text',
        'legacy_post_type' => '',
        'storage' => 'field_value',
        'status' => 'active',
        'sort_order' => 1,
    ]);

    $result = app(LegacyFieldPostTypeBackfillService::class)->backfill(true);

    expect($result['fields'])->toBe(2);
    expect(Field::query()->where('key', 'spa_id')->value('legacy_post_type'))->toBe('store');
    expect(Field::query()->where('key', 'lease_status')->value('legacy_post_type'))->toBe('franchise_location');
});
