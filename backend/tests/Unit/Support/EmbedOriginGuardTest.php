<?php

declare(strict_types=1);

use App\Support\Embed\EmbedOriginGuard;
use Illuminate\Http\Request;

test('embed origin guard skips enforcement outside staging and production', function () {
    $guard = new EmbedOriginGuard;

    expect($guard->allows(Request::create('/api/public/v1/leads', 'POST'), []))->toBeTrue();
});

test('embed origin guard fails closed when allowlist empty in production', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $guard = new EmbedOriginGuard;

    expect($guard->allows(Request::create('/api/public/v1/leads', 'POST'), []))->toBeFalse();
    expect($guard->rejectionMessage([]))->toBe('Embed origin allowlist is not configured.');
});

test('embed origin guard accepts allowlisted browser origin', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.url' => 'https://crm.client.com']);

    $guard = new EmbedOriginGuard;
    $request = Request::create('/api/public/v1/leads', 'POST');
    $request->headers->set('Origin', 'https://www.client.com');

    expect($guard->allows($request, ['https://www.client.com']))->toBeTrue();
});

test('embed origin guard always allows app origin for staff preview', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.url' => 'https://crm.client.com']);

    $guard = new EmbedOriginGuard;
    $request = Request::create('/api/public/v1/leads', 'POST');
    $request->headers->set('Origin', 'https://crm.client.com');

    expect($guard->allows($request, ['https://www.client.com']))->toBeTrue();
});

test('embed origin guard rejects unknown origin without header', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.url' => 'https://crm.client.com']);

    $guard = new EmbedOriginGuard;

    expect($guard->allows(Request::create('/api/public/v1/leads', 'POST'), ['https://www.client.com']))->toBeFalse();
});

test('embed origin guard resolves origin from referer when origin header missing', function () {
    app()->detectEnvironment(fn (): string => 'production');
    config(['app.url' => 'https://crm.client.com']);

    $guard = new EmbedOriginGuard;
    $request = Request::create('/api/public/v1/leads', 'POST');
    $request->headers->set('Referer', 'https://www.client.com/franchise/apply');

    expect($guard->allows($request, ['https://www.client.com']))->toBeTrue();
});
