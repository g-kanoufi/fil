<?php

declare(strict_types=1);

use App\Models\Field;
use App\Models\FieldGroup;
use App\Services\Legacy\LegacyAcfImportService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('legacy acf import assigns locations group to store entity and skips location status on leads', function () {
    $path = base_path('tests/fixtures/legacy-acf-mini');

    $result = app(LegacyAcfImportService::class)->importDirectory($path);

    expect($result['groups'])->toBe(2);

    $applications = FieldGroup::query()->where('key', 'applications')->firstOrFail();
    $locations = FieldGroup::query()->where('key', 'locations')->firstOrFail();

    expect($applications->title)->toBe('Applications')
        ->and($locations->title)->toBe('Locations');

    $leadStatus = Field::query()
        ->where('field_group_id', $applications->id)
        ->where('key', 'lead_status')
        ->first();

    expect($leadStatus)->not->toBeNull()
        ->and($leadStatus->entity)->toBe('lead')
        ->and($leadStatus->storage)->toBe('column')
        ->and($leadStatus->maps_to_column)->toBe('lead_status')
        ->and(collect($leadStatus->config['choices'])->firstWhere('value', '6')['label'])->toBe('Spoke with Prospect');

    $locationStatus = Field::query()
        ->where('field_group_id', $locations->id)
        ->where('key', 'status')
        ->first();

    expect($locationStatus)->not->toBeNull()
        ->and($locationStatus->entity)->toBe('store')
        ->and(collect($locationStatus->config['choices'])->firstWhere('value', '7')['label'])->toBe('Open');

    expect(Field::query()->where('entity', 'lead')->where('key', 'status')->exists())->toBeFalse();
});
