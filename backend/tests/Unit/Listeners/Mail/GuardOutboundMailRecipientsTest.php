<?php

declare(strict_types=1);

namespace Tests\Unit\Listeners\Mail;

use App\Listeners\Mail\GuardOutboundMailRecipients;
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mime\Email;
use Tests\TestCase;

final class GuardOutboundMailRecipientsTest extends TestCase
{
    public function test_listener_rewrites_recipients_outside_production(): void
    {
        $this->app['env'] = 'local';
        Config::set('fil-mail.sink_addresses', ['dev@fil.test']);

        $symfony = new Email();
        $symfony->to('real.prospect@customer.com');
        $symfony->subject('Subject');
        $symfony->text('Body');

        $event = new MessageSending($symfony, []);

        app(GuardOutboundMailRecipients::class)->handle($event);

        $this->assertTrue($symfony->getTo()[0]->getAddress() === 'dev@fil.test');
        $this->assertSame(
            'real.prospect@customer.com',
            $symfony->getHeaders()->get('X-FIL-Original-Recipients')?->getBodyAsString(),
        );
    }

    public function test_listener_leaves_recipients_untouched_in_production(): void
    {
        $this->app['env'] = 'production';
        Config::set('fil-mail.block_outbound', false);

        $symfony = new Email();
        $symfony->to('prospect@customer.com');

        $event = new MessageSending($symfony, []);

        app(GuardOutboundMailRecipients::class)->handle($event);

        $this->assertSame('prospect@customer.com', $symfony->getTo()[0]->getAddress());
    }
}
