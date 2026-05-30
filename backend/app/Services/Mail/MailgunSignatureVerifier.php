<?php

declare(strict_types=1);

namespace App\Services\Mail;

use Illuminate\Http\Request;

final class MailgunSignatureVerifier
{
    public function verify(Request $request): bool
    {
        $signingKey = (string) config('services.mailgun.webhook_signing_key');

        if ($signingKey === '') {
            return app()->environment('local', 'testing');
        }

        $timestamp = (string) $request->input('timestamp', '');
        $token = (string) $request->input('token', '');
        $signature = (string) $request->input('signature', '');

        if ($timestamp === '' || $token === '' || $signature === '') {
            return false;
        }

        if (abs(time() - (int) $timestamp) > 900) {
            return false;
        }

        $expected = hash_hmac('sha256', $timestamp.$token, $signingKey);

        return hash_equals($expected, $signature);
    }
}
