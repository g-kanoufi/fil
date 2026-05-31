<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Widget\WidgetFieldCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('widget field catalog allows applications and user groups only', function () {
    config(['fil.widget.allowed_field_group_keys' => ['applications', 'user']]);

    $applications = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);
    $user = FieldGroup::query()->create([
        'key' => 'user',
        'title' => 'User',
        'sort_order' => 2,
        'status' => 'active',
    ]);
    FieldGroup::query()->create([
        'key' => 'internal',
        'title' => 'Internal',
        'sort_order' => 3,
        'status' => 'active',
    ]);

    expect(WidgetFieldCatalog::allowedGroupKeys())->toBe(['applications', 'user']);
    expect(WidgetFieldCatalog::allowedGroupIds())->toEqualCanonicalizing([$applications->id, $user->id]);

    $allowedField = Field::query()->create([
        'field_group_id' => $applications->id,
        'entity' => 'lead',
        'key' => 'preferred_market',
        'name' => 'Preferred Market',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);
    $blockedField = Field::query()->create([
        'field_group_id' => FieldGroup::query()->where('key', 'internal')->value('id'),
        'entity' => 'lead',
        'key' => 'internal_margin_notes',
        'name' => 'Internal Margin Notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    expect(WidgetFieldCatalog::fieldIsAllowed($allowedField))->toBeTrue();
    expect(WidgetFieldCatalog::fieldIsAllowed($blockedField))->toBeFalse();
});
