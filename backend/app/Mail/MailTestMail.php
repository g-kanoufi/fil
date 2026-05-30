<?php

declare(strict_types=1);

namespace App\Mail;

use App\Mail\Concerns\UsesFilBrandedLayout;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

final class MailTestMail extends Mailable
{
    use Queueable, SerializesModels, UsesFilBrandedLayout;

    public function __construct(
        public readonly string $appName,
        public readonly string $activeMailer,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->appName.' — mail delivery test',
        );
    }

    public function content(): Content
    {
        $body = '<p>This is a test email from <strong>'.e($this->appName).'</strong> sent via the '
            .e($this->activeMailer).' mailer at '.e(now()->toIso8601String()).'.</p>';

        return $this->brandedHtmlContent($body);
    }
}
