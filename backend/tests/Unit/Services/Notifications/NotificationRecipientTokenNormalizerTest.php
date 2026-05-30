<?php

namespace Tests\Unit\Services\Notifications;

use App\Services\Notifications\NotificationRecipientTokenNormalizer;
use PHPUnit\Framework\TestCase;

class NotificationRecipientTokenNormalizerTest extends TestCase
{
    private NotificationRecipientTokenNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new NotificationRecipientTokenNormalizer;
    }

    public function test_normalizes_string_tokens(): void
    {
        $this->assertSame(
            ['related:prospect', 'admin@example.com'],
            $this->normalizer->normalizeList(['related:prospect', 'admin@example.com']),
        );
    }

    public function test_normalizes_wp_notification_repeater_objects(): void
    {
        $this->assertSame(
            ['related:prospect'],
            $this->normalizer->normalizeList([
                ['type' => 'email', 'recipient' => 'related:prospect'],
            ]),
        );

        $this->assertSame(
            ['related:lead_owner'],
            $this->normalizer->normalizeList([
                ['type' => 'email', 'recipient' => 'lead_owner'],
            ]),
        );

        $this->assertSame(
            ['role:administrator'],
            $this->normalizer->normalizeList([
                ['type' => 'role', 'recipient' => 'administrator'],
            ]),
        );
    }
}
