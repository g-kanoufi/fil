<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Outbound mail safety
    |--------------------------------------------------------------------------
    |
    | Outside production, FIL never delivers to real recipient addresses.
    | Mail is forced to a safe mailer (log/array) and recipients are rewritten
    | to sink addresses before transport.
    |
    | Set FIL_MAIL_SINK (comma-separated) to capture messages in a dev inbox
    | when using a non-log mailer in staging — recipients are still rewritten.
    |
    */
    'sink_addresses' => array_values(array_filter(array_map(
        trim(...),
        explode(',', (string) env(
            'FIL_MAIL_SINK',
            env('FIL_NOTIFICATION_DEV_EMAILS', ''),
        ))
    ))),

    /** Mailer used whenever APP_ENV is not production. */
    'non_production_mailer' => env('FIL_MAIL_NON_PRODUCTION_MAILER', 'log'),

    /** When true in production, block all outbound mail (dry-run). */
    'block_outbound' => (bool) env('FIL_MAIL_BLOCK_OUTBOUND', false),
];
