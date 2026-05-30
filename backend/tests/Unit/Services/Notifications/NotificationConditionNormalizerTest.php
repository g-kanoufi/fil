<?php

declare(strict_types=1);
use App\Services\Notifications\NotificationConditionNormalizer;

beforeEach(function () {
    $this->normalizer = app(NotificationConditionNormalizer::class);
});
test('legacy and with empty groups becomes always', function () {
    $result = $this->normalizer->normalize(['rule' => 'AND', 'groups' => []]);

    expect($result['mode'])->toBe('always');
    expect($result['groups'])->toBe([]);
});
test('legacy do rule maps to send if', function () {
    $result = $this->normalizer->normalize([
        'rule' => 'do',
        'groups' => [
            [
                [
                    'merge_tag' => '{postmeta/lead_temp}',
                    'operator' => 'equal',
                    'value' => 'hot',
                ],
            ],
        ],
    ]);

    expect($result['v'])->toBe(2);
    expect($result['mode'])->toBe('send_if');
    expect($result['groups'][0]['conditions'][0]['field'])->toBe('lead_temp');
    expect($result['groups'][0]['conditions'][0]['op'])->toBe('eq');
});
test('v2 schema passes through', function () {
    $input = [
        'v' => 2,
        'mode' => 'skip_if',
        'groups' => [
            [
                'match' => 'all',
                'conditions' => [
                    ['field' => 'pipeline_phase', 'op' => 'eq', 'value' => '5'],
                ],
            ],
        ],
    ];

    $result = $this->normalizer->normalize($input);

    expect($result['mode'])->toBe('skip_if');
    expect($result['groups'][0]['conditions'][0]['field'])->toBe('pipeline_phase');
});
