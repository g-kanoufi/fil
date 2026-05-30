<?php

declare(strict_types=1);

namespace App\Services\Ach;

use Illuminate\Http\Request;

final class DwollaSignatureVerifier
{
    public function verify(Request $request): bool
    {
        $secret = (string) config('services.dwolla.webhook_secret');

        if ($secret === '') {
            return app()->environment('local', 'testing');
        }

        $signature = (string) $request->header('X-Request-Signature-SHA-256', '');

        if ($signature === '') {
            return false;
        }

        $body = $request->getContent();
        $expected = hash_hmac('sha256', $body, $secret);

        return hash_equals($expected, $signature);
    }
}
