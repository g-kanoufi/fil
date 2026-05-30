<?php

declare(strict_types=1);

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class TwilioSmsService
{
    public function enabled(): bool
    {
        return filled(config('services.twilio.sid'))
            && filled(config('services.twilio.token'))
            && filled(config('services.twilio.from'));
    }

    public function send(string $to, string $body): ?string
    {
        if (! $this->enabled()) {
            Log::info('Twilio SMS skipped (not configured)', ['to' => $to]);

            return null;
        }

        $sid = (string) config('services.twilio.sid');
        $url = "https://api.twilio.com/2010-04-01/Accounts/{$sid}/Messages.json";

        $response = Http::withBasicAuth($sid, (string) config('services.twilio.token'))
            ->asForm()
            ->post($url, [
                'To' => $to,
                'From' => (string) config('services.twilio.from'),
                'Body' => $body,
            ]);

        if (! $response->successful()) {
            Log::warning('Twilio SMS failed', [
                'to' => $to,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return (string) ($response->json('sid') ?? null);
    }
}
