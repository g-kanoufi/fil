<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesFilBrandedLayout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;

final class DripStepMail extends Mailable
{
    use Queueable, SerializesModels, UsesFilBrandedLayout;

    public function __construct(
        public readonly string $subjectLine,
        public readonly string $bodyText,
        public readonly ?int $communicationId = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->subjectLine,
        )->using(function (Email $message): void {
            if ($this->communicationId !== null) {
                $message->getHeaders()->addTextHeader('X-FIL-Communication-Id', (string) $this->communicationId);
            }
        });
    }

    public function content(): Content
    {
        return $this->brandedHtmlContent($this->bodyText);
    }
}
