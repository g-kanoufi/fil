<?php

use App\Services\Legacy\LegacyDumpTableSchema;
use App\Services\Legacy\LegacyNamedTableImporter;
use App\Services\Legacy\LegacyNotificationImportService;
use App\Services\Notifications\NotificationConditionNormalizer;
use App\Services\Notifications\NotificationRecipientTokenNormalizer;
use App\Services\Notifications\NotificationScheduleNormalizer;

test('decode json handles sql escaped carrier payload', function () {
    $service = new LegacyNotificationImportService(
        new LegacyNamedTableImporter(new LegacyDumpTableSchema),
        new NotificationConditionNormalizer,
        new NotificationScheduleNormalizer,
        new NotificationRecipientTokenNormalizer,
    );

    $method = new ReflectionMethod(LegacyNotificationImportService::class, 'decodeJson');
    $method->setAccessible(true);

    $payload = '{\"subject\":\"Welcome\",\"body\":\"<p>Hi</p>\",\"recipients\":[\"related:prospect\"]}';
    $decoded = $method->invoke($service, $payload);

    expect($decoded['subject'] ?? null)->toBe('Welcome');
    expect($decoded['body'] ?? null)->toBe('<p>Hi</p>');
});
