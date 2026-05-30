<?php

declare(strict_types=1);
use App\Mail\DripStepMail;
use App\Mail\FddDeliveryMail;
use App\Mail\NotificationMail;
use App\Support\Mail\MailBranding;
use Illuminate\Support\Facades\View;

test('branded layout renders zorzees structure', function () {
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
});
test('notification mailable uses branded view', function () {
    $mail = new NotificationMail('Subject', '<p>Body</p>', deliveryId: 99);
    $content = $mail->content();

    expect($content->html)->toBe('mail.branded');
    expect($content->text)->toBe('mail.plain-text');
});
test('drip step mailable uses branded view', function () {
    $mail = new DripStepMail('Drip subject', "Line one.\nLine two.", communicationId: 42);
    $content = $mail->content();

    expect($content->html)->toBe('mail.branded');
});
test('fdd delivery mailable includes document reference', function () {
    $mail = new FddDeliveryMail('FDD 2026', 'doc-1', 7);
    $rendered = $mail->render();

    $this->assertStringContainsString('FDD 2026', $rendered);
    $this->assertStringContainsString('doc-1', $rendered);
    $this->assertStringContainsString('delivery #7', $rendered);
});
