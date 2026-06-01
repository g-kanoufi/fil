<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Support\Widget\WidgetFieldEligibility;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('widget eligibility reads acf default flag', function () {
    expect(WidgetFieldEligibility::fromAcfFlag(1))->toBeTrue()
        ->and(WidgetFieldEligibility::fromAcfFlag('1'))->toBeTrue()
        ->and(WidgetFieldEligibility::fromAcfFlag(0))->toBeFalse()
        ->and(WidgetFieldEligibility::fromAcfFlag(null))->toBeFalse();
});

test('widget eligibility requires config flag on field', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $eligible = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
        'config' => ['widget_eligible' => true],
    ]);

    $hidden = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'internal_margin',
        'name' => 'Internal margin',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 2,
        'status' => 'active',
        'config' => ['widget_eligible' => false],
    ]);

    expect(WidgetFieldEligibility::isEligible($eligible))->toBeTrue()
        ->and(WidgetFieldEligibility::isEligible($hidden))->toBeFalse();
});
