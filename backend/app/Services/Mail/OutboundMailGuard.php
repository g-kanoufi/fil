<?php

declare(strict_types=1);

namespace App\Services\Mail;

final class OutboundMailGuard
{
    public function allowsRealRecipients(): bool
    {
        if (! app()->isProduction()) {
            return false;
        }

        return ! (bool) config('fil-mail.block_outbound', false);
    }

    /**
     * @return list<string>
     */
    public function sinkAddresses(): array
    {
        /** @var list<string> $addresses */
        $addresses = config('fil-mail.sink_addresses', []);

        return $addresses;
    }

    public function enforceSafeMailer(): void
    {
        if ($this->allowsRealRecipients()) {
            return;
        }

        $safeMailers = ['log', 'array'];
        $current = (string) config('mail.default');

        if (! in_array($current, $safeMailers, true)) {
            config(['mail.default' => (string) config('fil-mail.non_production_mailer', 'log')]);
        }
    }

    /**
     * @param  list<string>  $intended
     * @return list<string>
     */
    public function resolveRecipients(array $intended): array
    {
        if ($this->allowsRealRecipients()) {
            return $intended;
        }

        $sink = $this->sinkAddresses();

        if ($sink !== []) {
            return $sink;
        }

        return ['mail-sink@fil.invalid'];
    }

    /**
     * @return array{guarded: bool, allows_real_recipients: bool, sink_addresses: list<string>, effective_mailer: string}
     */
    public function status(): array
    {
        $this->enforceSafeMailer();

        return [
            'guarded' => ! $this->allowsRealRecipients(),
            'allows_real_recipients' => $this->allowsRealRecipients(),
            'sink_addresses' => $this->sinkAddresses(),
            'effective_mailer' => (string) config('mail.default'),
        ];
    }
}
