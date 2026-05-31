<?php

declare(strict_types=1);

use App\Support\Fields\FieldChoiceSet;

test('normalizes legacy flat choice map', function () {
    $set = new FieldChoiceSet([
        '1' => 'New Lead',
        '6' => 'Spoke with Prospect',
    ]);

    expect($set->labelFor('6'))->toBe('Spoke with Prospect')
        ->and($set->all())->toHaveCount(2);
});

test('resolves labels via aliases and enriches metadata from defaults', function () {
    $set = new FieldChoiceSet([
        ['value' => '6', 'label' => 'Spoke with Prospect'],
        ['value' => '9', 'label' => 'Inactive'],
    ]);

    $defaults = [
        [
            'value' => 'engaged',
            'label' => 'Spoke with Prospect',
            'aliases' => ['6'],
            'meta' => ['pipeline_phase' => 3],
        ],
        [
            'value' => 'inactive',
            'label' => 'Inactive',
            'aliases' => ['9'],
            'meta' => ['closed' => true, 'pipeline_phase' => 99],
        ],
    ];

    $enriched = $set->enrichFromDefaults($defaults);

    expect($enriched[0]['meta']['pipeline_phase'])->toBe(3)
        ->and($enriched[1]['meta']['closed'])->toBeTrue()
        ->and($enriched[1]['meta']['pipeline_phase'])->toBe(99);

    $resolved = new FieldChoiceSet($enriched);

    expect($resolved->pipelinePhase('6'))->toBe(3)
        ->and($resolved->isClosed('9'))->toBeTrue();
});

test('matchChoice finds by alias', function () {
    $set = new FieldChoiceSet([
        [
            'value' => 'disclosed',
            'label' => 'FDD Sent',
            'aliases' => ['sent fdd'],
            'meta' => ['pipeline_phase' => 5],
        ],
    ]);

    expect($set->labelFor('sent fdd'))->toBe('FDD Sent')
        ->and($set->pipelinePhase('disclosed'))->toBe(5);
});
