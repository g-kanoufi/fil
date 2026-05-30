<?php

declare(strict_types=1);
use App\Services\Mail\OutboundMailGuard;
use Illuminate\Support\Facades\Config;

test('non production never allows real recipients', function () {
    $this->app['env'] = 'local';

    $guard = app(OutboundMailGuard::class);

    expect($guard->allowsRealRecipients())->toBeFalse();
});
test('production allows real recipients by default', function () {
    $this->app['env'] = 'production';
    Config::set('fil-mail.block_outbound', false);

    $guard = app(OutboundMailGuard::class);

    expect($guard->allowsRealRecipients())->toBeTrue();
});
test('production can block outbound mail', function () {
    $this->app['env'] = 'production';
    Config::set('fil-mail.block_outbound', true);

    $guard = app(OutboundMailGuard::class);

    expect($guard->allowsRealRecipients())->toBeFalse();
});
test('resolve recipients rewrites to sink outside production', function () {
    $this->app['env'] = 'local';
    Config::set('fil-mail.sink_addresses', ['dev@fil.test']);

    $guard = app(OutboundMailGuard::class);

    expect($guard->resolveRecipients(['prospect@example.com']))->toBe(['dev@fil.test']);
});
test('resolve recipients uses placeholder when no sink configured', function () {
    $this->app['env'] = 'staging';
    Config::set('fil-mail.sink_addresses', []);

    $guard = app(OutboundMailGuard::class);

    expect($guard->resolveRecipients(['prospect@example.com']))->toBe(['mail-sink@fil.invalid']);
});
test('enforce safe mailer downgrades mailgun outside production', function () {
    $this->app['env'] = 'local';
    Config::set('mail.default', 'mailgun');
    Config::set('fil-mail.non_production_mailer', 'log');

    app(OutboundMailGuard::class)->enforceSafeMailer();

    expect(config('mail.default'))->toBe('log');
});
