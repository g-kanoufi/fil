<?php

declare(strict_types=1);

namespace App\Services\Communications;

final class CommunicationProviderReadiness
{
    /**
     * @return array{
     *   mailgun: array{configured: bool, webhooks_ready: bool, signing_key_set: bool},
     *   twilio: array{configured: bool, webhooks_ready: bool, auth_token_set: bool},
     *   webhook_urls: array{mailgun_events: string, mailgun_inbound: string, twilio_inbound: string, twilio_status: string}
     * }
     */
    public function status(): array
    {
        $mailgunSecret = filled(config('services.mailgun.secret'));
        $mailgunDomain = filled(config('services.mailgun.domain'));
        $mailgunSigning = filled(config('services.mailgun.webhook_signing_key'));

        $twilioSid = filled(config('services.twilio.sid'));
        $twilioToken = filled(config('services.twilio.token'));
        $twilioFrom = filled(config('services.twilio.from'));

        return [
            'mailgun' => [
                'configured' => $mailgunSecret && $mailgunDomain,
                'webhooks_ready' => $mailgunSigning,
                'signing_key_set' => $mailgunSigning,
            ],
            'twilio' => [
                'configured' => $twilioSid && $twilioToken && $twilioFrom,
                'webhooks_ready' => $twilioToken,
                'auth_token_set' => $twilioToken,
            ],
            'webhook_urls' => [
                'mailgun_events' => url('/api/webhooks/mailgun'),
                'mailgun_inbound' => url('/api/webhooks/mailgun/inbound'),
                'twilio_inbound' => url('/api/webhooks/twilio/inbound'),
                'twilio_status' => url('/api/webhooks/twilio/status'),
            ],
        ];
    }
}
