<?php

declare(strict_types=1);

/**
 * Security hardening configuration (SEC-019, SEC-020).
 *
 * @see docs/SECURITY_AUDIT.md
 */
return [
    'csp' => [
        'enabled' => filled(env('FIL_CSP_ENABLED'))
            ? filter_var(env('FIL_CSP_ENABLED'), FILTER_VALIDATE_BOOLEAN)
            : env('APP_ENV') !== 'local',
        'report_only' => filled(env('FIL_CSP_REPORT_ONLY'))
            ? filter_var(env('FIL_CSP_REPORT_ONLY'), FILTER_VALIDATE_BOOLEAN)
            : false,

        /** Domains allowed to load scripts (Plaid Link, Dwolla drop-ins, reCAPTCHA). */
        'script_src' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) (env('FIL_CSP_SCRIPT_SRC') ?: implode(',', [
                'https://cdn.plaid.com',
                'https://cdn.dwolla.com',
                'https://www.google.com',
                'https://www.gstatic.com',
            ])))
        ))),

        /** Domains allowed for XHR/fetch (Plaid/Dwolla APIs, reCAPTCHA verify). */
        'connect_src' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) (env('FIL_CSP_CONNECT_SRC') ?: implode(',', [
                'https://*.plaid.com',
                'https://*.dwolla.com',
                'https://www.google.com',
            ])))
        ))),

        /** iframe embeds (reCAPTCHA challenge). */
        'frame_src' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) (env('FIL_CSP_FRAME_SRC') ?: implode(',', [
                'https://www.google.com',
                'https://recaptcha.google.com',
            ])))
        ))),
    ],

    'public_lead_intake' => [
        /** Per embed site_key submissions per minute (SEC-019). */
        'site_key_max_attempts' => (int) env('FIL_EMBED_SITE_KEY_RATE_LIMIT', 30),
        'site_key_decay_minutes' => 1,
    ],

    'legacy_import' => [
        /** Typed confirmation required with --force --execute in staging/production (SEC-023). */
        'confirm_token' => 'legacy-import',
    ],
];
