<?php

namespace Tests\Unit\Services\Legacy;

use App\Services\Legacy\LegacyNotificationImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class LegacyNotificationImportServiceTest extends TestCase
{
    public function test_decode_json_handles_sql_escaped_carrier_payload(): void
    {
        $service = new LegacyNotificationImportService(
            new \App\Services\Legacy\LegacyNamedTableImporter,
            new \App\Services\Notifications\NotificationConditionNormalizer,
            new \App\Services\Notifications\NotificationScheduleNormalizer,
            new \App\Services\Notifications\NotificationRecipientTokenNormalizer,
        );

        $method = new ReflectionMethod(LegacyNotificationImportService::class, 'decodeJson');
        $method->setAccessible(true);

        $payload = '{\"subject\":\"Welcome\",\"body\":\"<p>Hi</p>\",\"recipients\":[\"related:prospect\"]}';
        $decoded = $method->invoke($service, $payload);

        $this->assertSame('Welcome', $decoded['subject'] ?? null);
        $this->assertSame('<p>Hi</p>', $decoded['body'] ?? null);
    }
}
