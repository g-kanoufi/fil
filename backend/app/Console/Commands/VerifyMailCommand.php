<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Mail\MailgunVerificationService;
use Illuminate\Console\Command;

final class VerifyMailCommand extends Command
{
    protected $signature = 'mail:verify {--send= : Optional recipient for a test email}';

    protected $description = 'Verify Mailgun domain configuration and optionally send a test email';

    public function handle(MailgunVerificationService $mailgun): int
    {
        $status = $mailgun->status();

        $this->info('Mailer: '.$status['mailer']);
        $this->line('From: '.$status['from_name'].' <'.$status['from_address'].'>');

        if ($status['mailer'] !== 'mailgun') {
            $this->warn('MAIL_MAILER is not mailgun — production drips/notifications will not use Mailgun API.');

            return self::FAILURE;
        }

        $domain = $mailgun->verifyDomain();

        if ($domain['verified'] ?? false) {
            $this->info('Mailgun domain verified ('.($domain['state'] ?? 'active').').');
        } else {
            $this->error('Mailgun domain not verified: '.($domain['error'] ?? 'unknown error'));

            return self::FAILURE;
        }

        $recipient = (string) ($this->option('send') ?? '');

        if ($recipient !== '') {
            $result = $mailgun->sendTestEmail($recipient);

            if ($result['sent']) {
                $this->info('Test email sent to '.$result['recipient'].'.');
            } else {
                $this->error('Test email failed: '.($result['error'] ?? 'unknown error'));

                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }
}
