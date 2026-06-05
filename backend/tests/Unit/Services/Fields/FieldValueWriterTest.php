<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldValue;
use App\Models\Lead;
use App\Services\Fields\FieldValueWriter;

uses(Illuminate\Foundation\Testing\RefreshDatabase::class);

it('stores currency strings in value_number for number fields', function (): void {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'legacy_cash_test',
        'name' => 'Cash',
        'type' => 'number',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::query()->create([
        'title' => 'Test Lead',
        'pipeline_phase' => 1,
    ]);

    app(FieldValueWriter::class)->write('lead', $lead->id, [
        'legacy_cash_test' => '$150000.00',
    ]);

    $stored = FieldValue::query()
        ->where('entity_type', 'lead')
        ->where('entity_id', $lead->id)
        ->where('field_id', $field->id)
        ->first();

    expect($stored)->not->toBeNull()
        ->and((float) $stored->value_number)->toBe(150000.0);
});
