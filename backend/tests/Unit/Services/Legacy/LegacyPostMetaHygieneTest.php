<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Services\Legacy\LegacyPostMetaHygiene;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('hygiene allows registered scalar keys', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'legacy_post_type' => 'application',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $index = Field::query()->where('entity', 'lead')->get()->keyBy('key');
    $hygiene = app(LegacyPostMetaHygiene::class);

    expect($hygiene->shouldImport('referral_notes', 'lead', $index, false, null))->toBeTrue();
});

test('hygiene skips orphan keys', function () {
    $index = collect();
    $hygiene = app(LegacyPostMetaHygiene::class);

    expect($hygiene->shouldImport('legacy_only_key', 'lead', $index, false, null))->toBeFalse();
});
