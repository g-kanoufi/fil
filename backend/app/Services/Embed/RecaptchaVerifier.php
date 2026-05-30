<?php

declare(strict_types=1);

namespace App\Services\Embed;

use Illuminate\Support\Facades\Http;

final class RecaptchaVerifier
{
    public function enabled(): bool
    {
        return filled(config('fil.recaptcha.secret_key'));
    }

    public function verify(?string $token, ?string $remoteIp = null): bool
    {
        if (! $this->enabled()) {
            return true;
        }

        if ($token === null || trim($token) === '') {
            return false;
        }

        $response = Http::asForm()->post('https://www.google.com/recaptcha/api/siteverify', [
            'secret' => config('fil.recaptcha.secret_key'),
            'response' => $token,
            'remoteip' => $remoteIp,
        ]);

        if (! $response->successful()) {
            return false;
        }

        return (bool) $response->json('success');
    }
}
