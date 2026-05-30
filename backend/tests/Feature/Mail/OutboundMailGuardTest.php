<?php

declare(strict_types=1);
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Support\Facades\Config;

test('mailgun mailer is downgraded to log outside production', function () {
    $this->app['env'] = 'local';
    Config::set('mail.default', 'mailgun');

    app(OutboundMailGuard::class)->enforceSafeMailer();

    expect(config('mail.default'))->toBe('log');
});
