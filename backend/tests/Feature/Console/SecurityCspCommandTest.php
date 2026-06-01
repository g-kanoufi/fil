<?php

declare(strict_types=1);

use App\Support\Security\ContentSecurityPolicyBuilder;

test('security csp command succeeds when enabled', function () {
    config([
        'fil-security.csp.enabled' => true,
        'fil-security.csp.report_only' => false,
        'fil-security.csp.script_src' => ['https://cdn.plaid.com'],
    ]);

    $this->artisan('security:csp')->assertSuccessful();
});

test('security csp command fails when disabled', function () {
    config(['fil-security.csp.enabled' => false]);

    $this->artisan('security:csp')
        ->assertFailed()
        ->expectsOutputToContain('CSP is disabled');
});

test('content security policy builder includes integration domains', function () {
    config(['fil-security.csp.enabled' => true]);

    $policy = app(ContentSecurityPolicyBuilder::class)->build();

    expect($policy)->toContain('cdn.plaid.com')
        ->and($policy)->toContain('cdn.dwolla.com')
        ->and($policy)->toContain('recaptcha.google.com');
});
