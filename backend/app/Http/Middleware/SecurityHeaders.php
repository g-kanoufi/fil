<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Security\ContentSecurityPolicyBuilder;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security response headers (SEC-020 defense-in-depth).
 */
final class SecurityHeaders
{
    public function __construct(
        private readonly ContentSecurityPolicyBuilder $csp,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $headers = $response->headers;

        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('X-Permitted-Cross-Domain-Policies', 'none');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        $policy = $this->csp->build();

        if ($policy !== null) {
            $headers->set($this->csp->headerName(), $policy);
        }

        return $response;
    }
}
