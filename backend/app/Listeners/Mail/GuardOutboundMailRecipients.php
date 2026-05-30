<?php

declare(strict_types=1);

namespace App\Listeners\Mail;

use App\Services\Mail\OutboundMailGuard;
use Illuminate\Mail\Events\MessageSending;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Header\Headers;

final class GuardOutboundMailRecipients
{
    public function __construct(
        private readonly OutboundMailGuard $guard,
    ) {}

    public function handle(MessageSending $event): bool
    {
        if ($this->guard->allowsRealRecipients()) {
            return true;
        }

        $message = $event->message;

        if (! $message instanceof Email) {
            return true;
        }

        $original = $this->collectAddresses($message);

        if ($original !== []) {
            $message->getHeaders()->addTextHeader(
                'X-FIL-Original-Recipients',
                implode(', ', $original),
            );
        }

        $safe = $this->guard->resolveRecipients($original);
        $message->to(...array_map(
            static fn (string $email): Address => new Address($email),
            $safe,
        ));

        $this->clearCopyRecipients($message->getHeaders());

        return true;
    }

    /**
     * @return list<string>
     */
    private function collectAddresses(Email $message): array
    {
        $addresses = [];

        foreach (['getTo', 'getCc', 'getBcc'] as $method) {
            $items = $message->{$method}();

            if ($items === []) {
                continue;
            }

            foreach ($items as $address) {
                $addresses[] = $address->getAddress();
            }
        }

        return array_values(array_unique($addresses));
    }

    private function clearCopyRecipients(Headers $headers): void
    {
        if ($headers->has('cc')) {
            $headers->remove('cc');
        }

        if ($headers->has('bcc')) {
            $headers->remove('bcc');
        }
    }
}
