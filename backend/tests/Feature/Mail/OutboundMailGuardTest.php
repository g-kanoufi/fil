<?php

declare(strict_types=1);

namespace Tests\Feature\Mail;

use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class OutboundMailGuardTest extends TestCase
{
    public function test_mailgun_mailer_is_downgraded_to_log_outside_production(): void
    {
        $this->app['env'] = 'local';
        Config::set('mail.default', 'mailgun');

        app(\App\Services\Mail\OutboundMailGuard::class)->enforceSafeMailer();

        $this->assertSame('log', config('mail.default'));
    }
}
