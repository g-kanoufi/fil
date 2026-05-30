<?php

declare(strict_types=1);
use App\Models\Lead;
use App\Services\Notifications\NotificationConditionalEvaluator;

beforeEach(function () {
    $this->evaluator = app(NotificationConditionalEvaluator::class);
});
function conditionalLead(array $attributes = []): Lead
{
    return new Lead(array_merge([
        'title' => 'Test Application',
        'pipeline_phase' => 1,
        'status' => 'active',
    ], $attributes));
}
test('passes when conditionals disabled', function () {
    $lead = conditionalLead(['lead_temp' => 'hot']);

    expect($this->evaluator->shouldSend(null, $lead))->toBeTrue();
    expect($this->evaluator->shouldSend(['rule' => 'off'], $lead))->toBeTrue();
});
test('do rule requires matching group', function () {
    $lead = conditionalLead(['lead_temp' => 'hot']);

    $conditionals = [
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
    ];

    expect($this->evaluator->shouldSend($conditionals, $lead))->toBeTrue();
});
test('dont rule blocks matching group', function () {
    $lead = conditionalLead(['lead_fdd_status' => 'inactive']);

    $conditionals = [
        'rule' => 'dont',
        'groups' => [
            [
                [
                    'merge_tag' => '{postmeta/lead_fdd_status}',
                    'operator' => 'equal',
                    'value' => 'inactive',
                ],
            ],
        ],
    ];

    expect($this->evaluator->shouldSend($conditionals, $lead))->toBeFalse();
});
test('is updated operator checks changed fields', function () {
    $lead = conditionalLead(['lead_status' => '6']);

    $conditionals = [
        'rule' => 'do',
        'groups' => [
            [
                [
                    'merge_tag' => '{postmeta/lead_status}',
                    'operator' => 'is_updated',
                    'value' => '',
                ],
            ],
        ],
    ];

    expect($this->evaluator->shouldSend($conditionals, $lead, ['lead_status']))->toBeTrue();
    expect($this->evaluator->shouldSend($conditionals, $lead, []))->toBeFalse();
});
test('legacy and with empty groups always sends', function () {
    $lead = conditionalLead(['lead_temp' => 'cold']);

    expect($this->evaluator->shouldSend(['rule' => 'AND', 'groups' => []], $lead))->toBeTrue();
});
