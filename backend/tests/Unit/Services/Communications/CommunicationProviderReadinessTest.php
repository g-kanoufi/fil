<?php

declare(strict_types=1);

use App\Services\Communications\CommunicationProviderReadiness;

test('readiness reports webhook urls', function () {
    config([
        'services.mailgun.secret' => '',
        'services.mailgun.domain' => '',
        'services.mailgun.webhook_signing_key' => '',
        'services.twilio.sid' => '',
        'services.twilio.token' => '',
        'services.twilio.from' => '',
    ]);

    $status = app(CommunicationProviderReadiness::class)->status();

    expect($status['webhook_urls']['mailgun_events'])->toContain('/api/webhooks/mailgun');
    expect($status['mailgun']['configured'])->toBeFalse();
    expect($status['twilio']['configured'])->toBeFalse();
});

test('readiness flags mailgun when credentials set', function () {
    config([
        'services.mailgun.secret' => 'key',
        'services.mailgun.domain' => 'mg.example.com',
        'services.mailgun.webhook_signing_key' => 'signing',
    ]);

    $status = app(CommunicationProviderReadiness::class)->status();

    expect($status['mailgun']['configured'])->toBeTrue();
    expect($status['mailgun']['webhooks_ready'])->toBeTrue();
});
