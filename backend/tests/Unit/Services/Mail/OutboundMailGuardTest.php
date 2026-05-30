<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Mail;

use App\Services\Mail\OutboundMailGuard;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

final class OutboundMailGuardTest extends TestCase
{
    public function test_non_production_never_allows_real_recipients(): void
    {
        $this->app['env'] = 'local';

        $guard = app(OutboundMailGuard::class);

        $this->assertFalse($guard->allowsRealRecipients());
    }

    public function test_production_allows_real_recipients_by_default(): void
    {
        $this->app['env'] = 'production';
        Config::set('fil-mail.block_outbound', false);

        $guard = app(OutboundMailGuard::class);

        $this->assertTrue($guard->allowsRealRecipients());
    }

    public function test_production_can_block_outbound_mail(): void
    {
        $this->app['env'] = 'production';
        Config::set('fil-mail.block_outbound', true);

        $guard = app(OutboundMailGuard::class);

        $this->assertFalse($guard->allowsRealRecipients());
    }

    public function test_resolve_recipients_rewrites_to_sink_outside_production(): void
    {
        $this->app['env'] = 'local';
        Config::set('fil-mail.sink_addresses', ['dev@fil.test']);

        $guard = app(OutboundMailGuard::class);

        $this->assertSame(
            ['dev@fil.test'],
            $guard->resolveRecipients(['prospect@example.com']),
        );
    }

    public function test_resolve_recipients_uses_placeholder_when_no_sink_configured(): void
    {
        $this->app['env'] = 'staging';
        Config::set('fil-mail.sink_addresses', []);

        $guard = app(OutboundMailGuard::class);

        $this->assertSame(
            ['mail-sink@fil.invalid'],
            $guard->resolveRecipients(['prospect@example.com']),
        );
    }

    public function test_enforce_safe_mailer_downgrades_mailgun_outside_production(): void
    {
        $this->app['env'] = 'local';
        Config::set('mail.default', 'mailgun');
        Config::set('fil-mail.non_production_mailer', 'log');

        app(OutboundMailGuard::class)->enforceSafeMailer();

        $this->assertSame('log', config('mail.default'));
    }
}
