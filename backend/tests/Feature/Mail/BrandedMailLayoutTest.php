<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use App\Mail\DripStepMail;
use App\Mail\FddDeliveryMail;
use App\Mail\NotificationMail;
use App\Support\Mail\MailBranding;
use Illuminate\Support\Facades\View;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class BrandedMailLayoutTest extends TestCase
{
    #[Test]
    public function branded_layout_renders_zorzees_structure(): void
    {
        config([
            'fil-notifications.branding' => [
                'logo_url' => 'https://example.com/logo.png',
                'background_color' => '#f5f5f5',
                'brand_color' => '#53387B',
                'button_color' => '#999999',
                'footer_text' => 'Test footer',
            ],
            'app.name' => 'FIL',
            'app.url' => 'https://fil.test',
        ]);

        $html = View::make('mail.branded', [
            'bodyHtml' => '<p>Hello prospect</p><a class="g-btn" href="#">Next step</a>',
            'branding' => MailBranding::get(),
        ])->render();

        $this->assertStringContainsString('width="600"', $html);
        $this->assertStringContainsString('width="534"', $html);
        $this->assertStringContainsString('#53387B', $html);
        $this->assertStringContainsString('https://example.com/logo.png', $html);
        $this->assertStringContainsString('Hello prospect', $html);
        $this->assertStringContainsString('Test footer', $html);
        $this->assertStringContainsString('.g-btn', $html);
    }

    #[Test]
    public function notification_mailable_uses_branded_view(): void
    {
        $mail = new NotificationMail('Subject', '<p>Body</p>', deliveryId: 99);
        $content = $mail->content();

        $this->assertSame('mail.branded', $content->html);
        $this->assertSame('mail.plain-text', $content->text);
    }

    #[Test]
    public function drip_step_mailable_uses_branded_view(): void
    {
        $mail = new DripStepMail('Drip subject', "Line one.\nLine two.", communicationId: 42);
        $content = $mail->content();

        $this->assertSame('mail.branded', $content->html);
    }

    #[Test]
    public function fdd_delivery_mailable_includes_document_reference(): void
    {
        $mail = new FddDeliveryMail('FDD 2026', 'doc-1', 7);
        $rendered = $mail->render();

        $this->assertStringContainsString('FDD 2026', $rendered);
        $this->assertStringContainsString('doc-1', $rendered);
        $this->assertStringContainsString('delivery #7', $rendered);
    }
}
