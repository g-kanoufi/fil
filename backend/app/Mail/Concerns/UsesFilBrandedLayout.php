<?php

declare(strict_types=1);

namespace App\Mail\Concerns;

use App\Support\Mail\MailBranding;
use Illuminate\Mail\Mailables\Content;

trait UsesFilBrandedLayout
{
    protected function brandedHtmlContent(string $body): Content
    {
        $bodyHtml = MailBranding::prepareBodyHtml($body);

        return new Content(
            html: 'mail.branded',
            text: 'mail.plain-text',
            with: [
                'bodyHtml' => $bodyHtml,
                'bodyText' => MailBranding::plainTextFromHtml($bodyHtml !== '' ? $bodyHtml : $body),
                'branding' => MailBranding::get(),
            ],
        );
    }
}
