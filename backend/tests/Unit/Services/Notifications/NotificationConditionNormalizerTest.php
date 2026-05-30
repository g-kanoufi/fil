<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Notifications;

use App\Models\Lead;
use App\Services\Notifications\NotificationConditionNormalizer;
use Tests\TestCase;

final class NotificationConditionNormalizerTest extends TestCase
{
    private NotificationConditionNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = app(NotificationConditionNormalizer::class);
    }

    public function test_legacy_and_with_empty_groups_becomes_always(): void
    {
        $result = $this->normalizer->normalize(['rule' => 'AND', 'groups' => []]);

        $this->assertSame('always', $result['mode']);
        $this->assertSame([], $result['groups']);
    }

    public function test_legacy_do_rule_maps_to_send_if(): void
    {
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

        $this->assertSame(2, $result['v']);
        $this->assertSame('send_if', $result['mode']);
        $this->assertSame('lead_temp', $result['groups'][0]['conditions'][0]['field']);
        $this->assertSame('eq', $result['groups'][0]['conditions'][0]['op']);
    }

    public function test_v2_schema_passes_through(): void
    {
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

        $this->assertSame('skip_if', $result['mode']);
        $this->assertSame('pipeline_phase', $result['groups'][0]['conditions'][0]['field']);
    }
}
