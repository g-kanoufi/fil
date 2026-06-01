<?php

declare(strict_types=1);

namespace App\Support\Security;

/**
 * Builds a Content-Security-Policy header for the staff SPA shell (SEC-020).
 */
final class ContentSecurityPolicyBuilder
{
    public function build(): ?string
    {
        if (! (bool) config('fil-security.csp.enabled', false)) {
            return null;
        }

        $scriptSrc = array_merge(["'self'"], config('fil-security.csp.script_src', []));
        $connectSrc = array_merge(["'self'"], config('fil-security.csp.connect_src', []));
        $frameSrc = array_merge(["'self'"], config('fil-security.csp.frame_src', []));

        $directives = [
            "default-src 'self'",
            'script-src '.implode(' ', $scriptSrc),
            "style-src 'self' 'unsafe-inline'",
            "img-src 'self' data: https:",
            "font-src 'self' data:",
            'connect-src '.implode(' ', $connectSrc),
            'frame-src '.implode(' ', $frameSrc),
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
        ];

        return implode('; ', $directives);
    }

    public function headerName(): string
    {
        return (bool) config('fil-security.csp.report_only', false)
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';
    }
}
