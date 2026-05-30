<?php

declare(strict_types=1);
use App\Support\Mail\MailBranding;

it('returns zorzees compatible defaults', function () {
    config([
        'fil-notifications.branding' => [],
        'app.name' => 'FIL Test',
        'app.url' => 'https://fil.test',
    ]);

    $branding = MailBranding::get();

    expect($branding['background_color'])->toBe('#f5f5f5');
    expect($branding['brand_color'])->toBe('#53387B');
    expect($branding['button_color'])->toBe('#999999');
    expect($branding['app_name'])->toBe('FIL Test');
});
it('wraps plain text in paragraphs', function () {
    $html = MailBranding::prepareBodyHtml("Hello there.\n\nSecond line.");

    $this->assertStringContainsString('<p>Hello there.</p>', $html);
    $this->assertStringContainsString('<p>Second line.</p>', $html);
});
it('passes through existing html', function () {
    $input = '<p>Already <strong>HTML</strong></p>';
    expect(MailBranding::prepareBodyHtml($input))->toBe($input);
});
it('strips html for plain text fallback', function () {
    $plain = MailBranding::plainTextFromHtml('<p>Hello <strong>world</strong></p>');

    expect($plain)->toBe('Hello world');
});
