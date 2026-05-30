<?php

declare(strict_types=1);

namespace Tests\Unit\Mail;

use App\Support\Mail\MailBranding;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class MailBrandingTest extends TestCase
{
    #[Test]
    public function it_returns_zorzees_compatible_defaults(): void
    {
        config([
            'fil-notifications.branding' => [],
            'app.name' => 'FIL Test',
            'app.url' => 'https://fil.test',
        ]);

        $branding = MailBranding::get();

        $this->assertSame('#f5f5f5', $branding['background_color']);
        $this->assertSame('#53387B', $branding['brand_color']);
        $this->assertSame('#999999', $branding['button_color']);
        $this->assertSame('FIL Test', $branding['app_name']);
    }

    #[Test]
    public function it_wraps_plain_text_in_paragraphs(): void
    {
        $html = MailBranding::prepareBodyHtml("Hello there.\n\nSecond line.");

        $this->assertStringContainsString('<p>Hello there.</p>', $html);
        $this->assertStringContainsString('<p>Second line.</p>', $html);
    }

    #[Test]
    public function it_passes_through_existing_html(): void
    {
        $input = '<p>Already <strong>HTML</strong></p>';
        $this->assertSame($input, MailBranding::prepareBodyHtml($input));
    }

    #[Test]
    public function it_strips_html_for_plain_text_fallback(): void
    {
        $plain = MailBranding::plainTextFromHtml('<p>Hello <strong>world</strong></p>');

        $this->assertSame('Hello world', $plain);
    }
}
