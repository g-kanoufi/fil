<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

/**
 * Per embed site_key rate limit on public lead intake (SEC-019).
 */
final class ThrottleEmbedLeadIntake
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! app()->environment('production', 'staging')) {
            return $next($request);
        }

        $siteKey = (string) ($request->input('site_key') ?: $request->header('X-FIL-Site-Key', ''));

        if ($siteKey === '') {
            return $next($request);
        }

        $maxAttempts = (int) config('fil-security.public_lead_intake.site_key_max_attempts', 30);
        $decayMinutes = (int) config('fil-security.public_lead_intake.site_key_decay_minutes', 1);
        $key = 'embed-lead-intake:'.sha1($siteKey);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $retryAfter = RateLimiter::availableIn($key);

            return response()->json([
                'message' => 'Too many submissions for this form. Please try again later.',
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        return $next($request);
    }
}
