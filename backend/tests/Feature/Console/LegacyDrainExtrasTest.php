<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Models\FieldValue;
use App\Models\Lead;
use App\Services\Legacy\LegacyExtrasKeyResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('drain extras promotes schema keys into field_values', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::query()->create([
        'title' => 'Jane Applicant',
        'pipeline_phase' => 1,
        'extras' => [
            'referral_notes' => 'Met at expo',
            'legacy_only_key' => 'keep me',
        ],
    ]);

    $this->artisan('legacy:drain-extras', ['--entity' => 'leads', '--execute' => true])
        ->assertSuccessful();

    $this->assertDatabaseHas('field_values', [
        'entity_type' => 'lead',
        'entity_id' => $lead->id,
        'field_id' => $field->id,
        'value_text' => 'Met at expo',
    ]);

    $lead->refresh();

    expect($lead->extras)->toBe(['legacy_only_key' => 'keep me']);
    expect(FieldValue::query()->count())->toBe(1);
});

test('drain extras dry run does not write', function () {
    FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => FieldGroup::query()->first()->id,
        'entity' => 'lead',
        'key' => 'referral_notes',
        'name' => 'Referral notes',
        'type' => 'textarea',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Lead::query()->create([
        'title' => 'Jane Applicant',
        'pipeline_phase' => 1,
        'extras' => ['referral_notes' => 'Met at expo'],
    ]);

    $this->artisan('legacy:drain-extras', ['--entity' => 'leads'])
        ->assertSuccessful();

    expect(FieldValue::query()->count())->toBe(0);
});

test('extras key resolver maps repeater rows to parent field', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'next_steps',
        'name' => 'Next steps',
        'type' => 'textarea',
        'storage' => 'field_value',
        'config' => ['legacy_acf_type' => 'repeater'],
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $resolver = app(LegacyExtrasKeyResolver::class);
    $fields = Field::query()->where('entity', 'lead')->get()->keyBy('key');

    $resolved = $resolver->resolve('next_steps_0_next_step_description', $fields);

    expect($resolved?->isRepeaterRow())->toBeTrue();
    expect($resolved?->fieldKey)->toBe('next_steps');
    expect($resolved?->rowIndex)->toBe(0);
    expect($resolved?->subKey)->toBe('next_step_description');
});

test('extras key resolver maps group flattened keys to subfields', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'application_google_ad_campaign',
        'name' => 'Google ad campaign',
        'type' => 'text',
        'storage' => 'field_value',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $resolver = app(LegacyExtrasKeyResolver::class);
    $fields = Field::query()->where('entity', 'lead')->get()->keyBy('key');

    $resolved = $resolver->resolve('application_google_ad_group_application_google_ad_campaign', $fields);

    expect($resolved?->isScalar())->toBeTrue();
    expect($resolved?->fieldKey)->toBe('application_google_ad_campaign');
});

test('drain extras aggregates repeater rows into json field_values', function () {
    $group = FieldGroup::query()->create([
        'key' => 'applications',
        'title' => 'Applications',
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $field = Field::query()->create([
        'field_group_id' => $group->id,
        'entity' => 'lead',
        'key' => 'next_steps',
        'name' => 'Next steps',
        'type' => 'textarea',
        'storage' => 'field_value',
        'config' => ['legacy_acf_type' => 'repeater'],
        'sort_order' => 1,
        'status' => 'active',
    ]);

    $lead = Lead::query()->create([
        'title' => 'Jane Applicant',
        'pipeline_phase' => 1,
        'extras' => [
            'next_steps' => '1',
            'next_steps_0_next_step_description' => 'Call back',
            'next_steps_0_next_step_date' => '2024-01-02',
            'legacy_only_key' => 'keep me',
        ],
    ]);

    $this->artisan('legacy:drain-extras', ['--entity' => 'leads', '--execute' => true])
        ->assertSuccessful();

    $value = FieldValue::query()
        ->where('entity_type', 'lead')
        ->where('entity_id', $lead->id)
        ->where('field_id', $field->id)
        ->value('value_text');

    expect($value)->toBeJson();
    expect(json_decode((string) $value, true))->toBe([
        [
            'next_step_description' => 'Call back',
            'next_step_date' => '2024-01-02',
        ],
    ]);

    $lead->refresh();

    expect($lead->extras)->toBe(['legacy_only_key' => 'keep me']);
});
