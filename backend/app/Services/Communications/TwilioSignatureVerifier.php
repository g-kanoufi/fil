<?php

declare(strict_types=1);

namespace App\Services\Communications;

use Illuminate\Http\Request;

final class TwilioSignatureVerifier
{
    public function verify(Request $request): bool
    {
        $authToken = (string) config('services.twilio.token');

        if ($authToken === '') {
            return app()->environment('local', 'testing');
        }

        $signature = (string) $request->header('X-Twilio-Signature', '');

        if ($signature === '') {
            return false;
        }

        $expected = $this->computeSignature($request->fullUrl(), $request->post(), $authToken);

        return hash_equals($expected, $signature);
    }

    /**
     * @param  array<string, mixed>  $params
     */
    public function computeSignature(string $url, array $params, string $authToken): string
    {
        ksort($params);

        $data = $url;

        foreach ($params as $key => $value) {
            if (is_array($value)) {
                continue;
            }

            $data .= $key.(string) $value;
        }

        return base64_encode(hash_hmac('sha1', $data, $authToken, true));
    }
}
