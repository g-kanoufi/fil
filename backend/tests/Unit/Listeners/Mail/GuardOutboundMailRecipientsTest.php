<?php

declare(strict_types=1);
use App\Listeners\Mail\GuardOutboundMailRecipients;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Config;
use Symfony\Component\Mime\Email;

test('listener rewrites recipients outside production', function () {
    $this->app['env'] = 'local';
    Config::set('fil-mail.sink_addresses', ['dev@fil.test']);

    $symfony = new Email;
    $symfony->to('real.prospect@customer.com');
    $symfony->subject('Subject');
    $symfony->text('Body');

    $event = new MessageSending($symfony, []);

    app(GuardOutboundMailRecipients::class)->handle($event);

    expect($symfony->getTo()[0]->getAddress() === 'dev@fil.test')->toBeTrue();
    expect($symfony->getHeaders()->get('X-FIL-Original-Recipients')?->getBodyAsString())->toBe('real.prospect@customer.com');
});
test('listener leaves recipients untouched in production', function () {
    $this->app['env'] = 'production';
    Config::set('fil-mail.block_outbound', false);

    $symfony = new Email;
    $symfony->to('prospect@customer.com');

    $event = new MessageSending($symfony, []);

    app(GuardOutboundMailRecipients::class)->handle($event);

    expect($symfony->getTo()[0]->getAddress())->toBe('prospect@customer.com');
});
