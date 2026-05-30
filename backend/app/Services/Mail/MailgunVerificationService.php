<?php

declare(strict_types=1);

namespace App\Services\Mail;

use App\Mail\MailTestMail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

final class MailgunVerificationService
{
    public function __construct(
        private readonly OutboundMailGuard $mailGuard,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function status(): array
    {
        $mailer = (string) config('mail.default');
        $domain = (string) config('services.mailgun.domain');
        $secret = (string) config('services.mailgun.secret');

        $mailgunConfigured = $domain !== '' && $secret !== '';
        $usingMailgun = $mailer === 'mailgun';

        $domainCheck = $mailgunConfigured
            ? $this->fetchDomainState($domain)
            : ['verified' => false, 'state' => null, 'error' => 'Mailgun credentials not configured'];

        return [
            'mailer' => $mailer,
            'using_mailgun' => $usingMailgun,
            'configured' => $usingMailgun && $mailgunConfigured && ($domainCheck['verified'] ?? false),
            'from_address' => (string) config('mail.from.address'),
            'from_name' => (string) config('mail.from.name'),
            'outbound' => $this->mailGuard->status(),
            'mailgun' => [
                'domain' => $domain !== '' ? $domain : null,
                'domain_state' => $domainCheck['state'] ?? null,
                'verified' => (bool) ($domainCheck['verified'] ?? false),
                'error' => $domainCheck['error'] ?? null,
                'webhook_signing_configured' => filled(config('services.mailgun.webhook_signing_key')),
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function verifyDomain(): array
    {
        $domain = (string) config('services.mailgun.domain');

        if ($domain === '' || ! filled(config('services.mailgun.secret'))) {
            return [
                'verified' => false,
                'state' => null,
                'error' => 'Set MAIL_MAILER=mailgun, MAILGUN_DOMAIN, and MAILGUN_SECRET before verifying.',
            ];
        }

        return $this->fetchDomainState($domain);
    }

    /**
     * @return array{sent: bool, recipient: string, intended_recipient: string, error: string|null}
     */
    public function sendTestEmail(string $recipient): array
    {
        $recipient = trim($recipient);

        if ($recipient === '') {
            return [
                'sent' => false,
                'recipient' => $recipient,
                'intended_recipient' => $recipient,
                'error' => 'Recipient email is required.',
            ];
        }

        $deliverTo = $this->mailGuard->resolveRecipients([$recipient])[0];

        try {
            Mail::to($deliverTo)->send(new MailTestMail(
                appName: (string) config('app.name', 'FIL'),
                activeMailer: (string) config('mail.default'),
            ));
        } catch (TransportExceptionInterface $exception) {
            return [
                'sent' => false,
                'recipient' => $deliverTo,
                'intended_recipient' => $recipient,
                'error' => $exception->getMessage(),
            ];
        } catch (\Throwable $exception) {
            return [
                'sent' => false,
                'recipient' => $deliverTo,
                'intended_recipient' => $recipient,
                'error' => $exception->getMessage(),
            ];
        }

        return [
            'sent' => true,
            'recipient' => $deliverTo,
            'intended_recipient' => $recipient,
            'error' => null,
        ];
    }

    /**
     * @return array{verified: bool, state: string|null, error: string|null}
     */
    private function fetchDomainState(string $domain): array
    {
        $endpoint = rtrim((string) config('services.mailgun.endpoint', 'api.mailgun.net'), '/');
        $secret = (string) config('services.mailgun.secret');

        try {
            $response = Http::withBasicAuth('api', $secret)
                ->acceptJson()
                ->timeout(10)
                ->get("https://{$endpoint}/v3/domains/{$domain}");

            if (! $response->successful()) {
                return [
                    'verified' => false,
                    'state' => null,
                    'error' => $response->json('message') ?? $response->body(),
                ];
            }

            /** @var array<string, mixed>|null $domainPayload */
            $domainPayload = $response->json('domain');

            $state = is_array($domainPayload)
                ? (string) ($domainPayload['state'] ?? '')
                : '';

            $verified = in_array($state, ['active', 'verified'], true);

            return [
                'verified' => $verified,
                'state' => $state !== '' ? $state : null,
                'error' => $verified ? null : 'Mailgun domain state is '.$state,
            ];
        } catch (\Throwable $exception) {
            return [
                'verified' => false,
                'state' => null,
                'error' => $exception->getMessage(),
            ];
        }
    }
}
