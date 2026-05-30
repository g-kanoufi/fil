<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Models\Lead;
use App\Services\Notifications\NotificationConditionalEvaluator;
use Tests\TestCase;

final class NotificationConditionalEvaluatorTest extends TestCase
{
    private NotificationConditionalEvaluator $evaluator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->evaluator = app(NotificationConditionalEvaluator::class);
    }

    private function lead(array $attributes = []): Lead
    {
        return new Lead(array_merge([
            'title' => 'Test Application',
            'pipeline_phase' => 1,
            'status' => 'active',
        ], $attributes));
    }

    public function test_passes_when_conditionals_disabled(): void
    {
        $lead = $this->lead(['lead_temp' => 'hot']);

        $this->assertTrue($this->evaluator->shouldSend(null, $lead));
        $this->assertTrue($this->evaluator->shouldSend(['rule' => 'off'], $lead));
    }

    public function test_do_rule_requires_matching_group(): void
    {
        $lead = $this->lead(['lead_temp' => 'hot']);

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

        $this->assertTrue($this->evaluator->shouldSend($conditionals, $lead));
    }

    public function test_dont_rule_blocks_matching_group(): void
    {
        $lead = $this->lead(['lead_fdd_status' => 'inactive']);

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

        $this->assertFalse($this->evaluator->shouldSend($conditionals, $lead));
    }

    public function test_is_updated_operator_checks_changed_fields(): void
    {
        $lead = $this->lead(['lead_status' => '6']);

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

        $this->assertTrue($this->evaluator->shouldSend($conditionals, $lead, ['lead_status']));
        $this->assertFalse($this->evaluator->shouldSend($conditionals, $lead, []));
    }

    public function test_legacy_and_with_empty_groups_always_sends(): void
    {
        $lead = $this->lead(['lead_temp' => 'cold']);

        $this->assertTrue($this->evaluator->shouldSend(['rule' => 'AND', 'groups' => []], $lead));
    }
}
