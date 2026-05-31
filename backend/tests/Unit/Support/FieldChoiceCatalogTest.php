<?php

declare(strict_types=1);

use App\Models\Field;
use App\Support\Fields\FieldChoiceCatalog;
use Database\Seeders\FieldSchemaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('field choice catalog resolves labels and semantics from seeded schema', function () {
    $this->seed(FieldSchemaSeeder::class);

    $catalog = app(FieldChoiceCatalog::class);

    expect($catalog->label('lead', 'lead_status', '6'))->toBe('Spoke with Prospect')
        ->and($catalog->label('lead', 'lead_fdd_status', 'disclosed'))->toBe('FDD Sent')
        ->and($catalog->pipelinePhaseHint('lead', 'lead_fdd_status', 'waiting_period'))->toBe(8)
        ->and($catalog->isClosed('lead', 'lead_status', '9'))->toBeTrue()
        ->and($catalog->isSystemField('lead', 'lead_status'))->toBeTrue();
});

test('admin-edited choice labels are preserved on re-seed enrichment', function () {
    $this->seed(FieldSchemaSeeder::class);

    $field = Field::query()->where('key', 'lead_status')->firstOrFail();
    $choices = $field->config['choices'];
    $choices[0]['label'] = 'Brand New Lead';
    $field->update(['config' => ['choices' => $choices, 'system' => true]]);

    $this->seed(FieldSchemaSeeder::class);

    $field->refresh();
    $first = collect($field->config['choices'])->first();

    expect($first['label'])->toBe('Brand New Lead');
});

test('falls back to legacy pipeline labels when schema has no choices', function () {
    $catalog = app(FieldChoiceCatalog::class);

    expect($catalog->label('lead', 'lead_status', '14'))
        ->toBe('Award Franchise (Agreement Signed)');
});
