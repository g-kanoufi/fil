<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesFilBrandedLayout;
use App\Support\Mail\MailBranding;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

final class NotificationMail extends Mailable
{
    use Queueable, SerializesModels, UsesFilBrandedLayout;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyHtml,
        public readonly ?int $deliveryId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        )->using(function (Email $message): void {
            if ($this->deliveryId !== null) {
                $message->getHeaders()->addTextHeader('X-FIL-Delivery-Id', (string) $this->deliveryId);
            }
        });
    }

    public function content(): Content
    {
        return new Content(
            html: 'mail.branded',
            text: 'mail.plain-text',
            with: [
                'bodyHtml' => MailBranding::prepareBodyHtml($this->bodyHtml),
                'bodyText' => MailBranding::plainTextFromHtml($this->bodyHtml),
                'branding' => MailBranding::get(),
            ],
        );
    }
}
