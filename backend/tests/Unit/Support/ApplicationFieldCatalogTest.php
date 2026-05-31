<?php

declare(strict_types=1);

use App\Support\Fields\ApplicationFieldCatalog;

test('application field catalog exposes zorzees lead status choices not store status', function () {
    $choices = ApplicationFieldCatalog::leadStatusChoices();

    expect($choices)->toHaveKeys(['1', '6', '14', '15'])
        ->and($choices['1'])->toBe('New Lead')
        ->and($choices['6'])->toBe('Spoke with Prospect')
        ->and($choices['14'])->toBe('Award Franchise (Agreement Signed)')
        ->and($choices['15'])->toBe('Award Area (Master Agreement Signed)');

    expect($choices)->not->toHaveKey('open')
        ->and($choices)->not->toHaveKey('pending')
        ->and($choices)->not->toHaveKey('closed');
});

test('excluded lead field keys include location and store status fields', function () {
    $excluded = ApplicationFieldCatalog::excludedLeadFieldKeys();

    expect($excluded)->toContain('status', 'store_status', 'open', 'closed');
});

test('tier one fields map to lead columns', function () {
    $fields = ApplicationFieldCatalog::tierOneFields();
    $keys = array_column($fields, 'key');

    expect($keys)->toEqual([
        'lead_stage',
        'lead_status',
        'lead_temp',
        'lead_fdd_status',
        'lead_source',
    ]);

    foreach ($fields as $field) {
        expect($field['storage'])->toBe('column')
            ->and($field['maps_to_column'])->toBe($field['key']);
    }
});
