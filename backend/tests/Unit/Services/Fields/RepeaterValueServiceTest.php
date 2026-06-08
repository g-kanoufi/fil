<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldRepeaterRow;
use App\Models\Lead;
use App\Services\Fields\EntityFieldValueReader;
use App\Services\Fields\RepeaterValueReader;
use App\Services\Fields\RepeaterValueWriter;
use App\Support\Fields\FieldTypes;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('repeater writer and reader round trip rows', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $parent = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'next_steps',
        'name' => 'Next steps',
        'type' => FieldTypes::REPEATER,
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'parent_field_id' => $parent->id,
        'entity' => 'lead',
        'key' => 'next_step_description',
        'name' => 'Description',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::query()->create([
        'title' => 'Jane Applicant',
        'pipeline_phase' => 1,
    ]);

    app(RepeaterValueWriter::class)->write('lead', $lead->id, $parent, [
        ['next_step_description' => 'Follow up'],
    ]);

    expect(FieldRepeaterRow::query()->where('field_id', $parent->id)->count())->toBe(1);

    $rows = app(RepeaterValueReader::class)->read('lead', $lead->id, $parent);

    expect($rows)->toBe([
        ['next_step_description' => 'Follow up'],
    ]);
});

test('entity field value reader exposes repeater rows by field key', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $parent = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'next_steps',
        'name' => 'Next steps',
        'type' => FieldTypes::REPEATER,
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'parent_field_id' => $parent->id,
        'entity' => 'lead',
        'key' => 'next_step_description',
        'name' => 'Description',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::query()->create([
        'title' => 'Jane Applicant',
        'pipeline_phase' => 1,
    ]);

    app(RepeaterValueWriter::class)->write('lead', $lead->id, $parent, [
        ['next_step_description' => 'Call back'],
    ]);

    $custom = app(EntityFieldValueReader::class)->forEntity('lead', $lead->id);

    expect($custom['next_steps'])->toBe([
        ['next_step_description' => 'Call back'],
    ]);
});
