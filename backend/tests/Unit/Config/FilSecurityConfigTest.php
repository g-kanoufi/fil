<?php

declare(strict_types=1);

test('csp config uses integration defaults when env vars are empty strings', function () {
    putenv('FIL_CSP_SCRIPT_SRC=');
    putenv('FIL_CSP_CONNECT_SRC=');
    putenv('FIL_CSP_FRAME_SRC=');
    putenv('FIL_CSP_ENABLED=');

    $this->refreshApplication();

    expect(config('fil-security.csp.script_src'))->toContain('https://cdn.plaid.com')
        ->and(config('fil-security.csp.connect_src'))->toContain('https://*.plaid.com')
        ->and(config('fil-security.csp.frame_src'))->toContain('https://recaptcha.google.com');
});
