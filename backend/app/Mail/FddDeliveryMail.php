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

final class FddDeliveryMail extends Mailable
{
    use Queueable, SerializesModels, UsesFilBrandedLayout;

    public function __construct(
        public readonly string $fddTitle,
        public readonly string $documentId,
        public readonly int $deliveryId,
        public readonly bool $isReminder = false,
    ) {}

    public function envelope(): Envelope
    {
        $subject = $this->isReminder
            ? 'Franchise Disclosure Document — Reminder'
            : 'Franchise Disclosure Document';

        return new Envelope(
            subject: $subject,
        )->using(function (Email $message): void {
            $message->getHeaders()->addTextHeader('X-FIL-Fdd-Delivery-Id', (string) $this->deliveryId);
        });
    }

    public function content(): Content
    {
        $intro = $this->isReminder
            ? 'This is a reminder that your Franchise Disclosure Document is ready for review.'
            : 'Your Franchise Disclosure Document is ready for review.';

        $body = '<p>'.e($intro).'</p>'
            .'<p><strong>'.e($this->fddTitle).'</strong></p>'
            .'<p>Document ID: '.e($this->documentId).'<br/>'
            .'Reference: delivery #'.e((string) $this->deliveryId).'</p>'
            .'<p>Please contact your franchise development representative if you have any questions.</p>';

        return $this->brandedHtmlContent($body);
    }
}
