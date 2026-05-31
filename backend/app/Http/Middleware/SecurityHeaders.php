<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers (SEC-020 defense-in-depth).
 *
 * Note: a strict Content-Security-Policy is intentionally NOT set here. The app
 * loads external scripts at runtime (Plaid Link, Dwolla drop-ins, reCAPTCHA) and
 * ships an embeddable widget, so a CSP needs a vetted domain allowlist + browser
 * validation before it can be enabled. Tracked in docs/SECURITY_AUDIT.md.
 */
final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        return $response;
    }
}
