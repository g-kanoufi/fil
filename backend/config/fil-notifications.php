<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Queue names — run dedicated workers for high-volume email throughput
    |--------------------------------------------------------------------------
    | php artisan queue:work redis --queue=emails,notifications,default
    */
    'queues' => [
        'notifications' => env('FIL_NOTIFICATIONS_QUEUE', 'notifications'),
        'emails' => env('FIL_EMAILS_QUEUE', 'emails'),
    ],

    /** @deprecated Use FIL_MAIL_SINK — kept for backward compatibility. */
    'dev_redirect_to' => config('fil-mail.sink_addresses', []),

    /** Max recipients per rule per trigger fire (safety cap). */
    'max_recipients_per_send' => (int) env('FIL_NOTIFICATION_MAX_RECIPIENTS', 50),

    /** Max leads processed per scheduled notification batch. */
    'scheduled_batch_size' => (int) env('FIL_NOTIFICATION_SCHEDULED_BATCH', 200),

    /*
    |--------------------------------------------------------------------------
    | Trigger slugs (map legacy import slugs to these constants)
    |--------------------------------------------------------------------------
    */
    'triggers' => [
        'lead.created' => 'Lead created (widget/intake)',
        'lead.updated' => 'Lead updated',
        'lead.phase_changed' => 'Pipeline phase changed',
        'lead.status_changed' => 'Lead status changed',
        'lead.fdd_status_changed' => 'Lead FDD status changed',
        'application.send_prospect_fdd' => 'FDD sent to prospect',
        'application.fdd_signed' => 'FDD receipt signed',
        'application.waiting_period_over' => 'Waiting period ended',
        'application.long_form_updated' => 'Long form updated',
        'user.registered' => 'User registered',
        'user.profile_updated' => 'User profile updated',
        'scheduled.leads' => 'Scheduled date-based (leads)',
        'store.inspection_due' => 'Store inspection due',
    ],

    /** Legacy merge-tag field → Lead column */
    'field_map' => [
        'waiting_period_end_date' => 'waiting_period_ends_at',
        'fdd_signed_date' => 'fdd_signed_at',
        'lead_status' => 'lead_status',
        'lead_fdd_status' => 'lead_fdd_status',
        'lead_temp' => 'lead_temp',
        'pipeline_phase' => 'pipeline_phase',
    ],

    /** Legacy schedule merge-tag field → Lead datetime column */
    'schedule_field_map' => [
        'waiting_period_end_date' => 'waiting_period_ends_at',
        'fdd_signed_date' => 'fdd_signed_at',
        'created_date' => 'created_at',
    ],

    /** Days to scan backward/forward when evaluating scheduled rules */
    'schedule_lookback_days' => (int) env('FIL_NOTIFICATION_SCHEDULE_LOOKBACK', 90),
    'schedule_lookahead_days' => (int) env('FIL_NOTIFICATION_SCHEDULE_LOOKAHEAD', 7),

    /** When false, skip user.registered emitter (useful in tests). */
    'emit_user_registered' => (bool) env('FIL_EMIT_USER_REGISTERED', true),

    /** Legacy trigger slug → FIL trigger slug */
    'legacy_trigger_map' => [
        'post/application/send-prospect-fdd' => 'application.send_prospect_fdd',
        'post/application/send-prospect-fdd-not-available' => 'application.send_prospect_fdd',
        'post/application/send-full-application' => 'lead.created',
        'post/application/send-full-application-fdd-signed' => 'application.fdd_signed',
        'post/application/application-long-form-updated' => 'application.long_form_updated',
        'post/application/zrz-updated' => 'lead.updated',
        'post/application/zrz-published' => 'lead.created',
        'post/application/zrz-send-one-campaign' => 'scheduled.leads',
        'post/application/handle_twilio_webhook' => 'lead.updated',
        'user/zrz-profile_updated' => 'user.profile_updated',
        'user/zrz_profile_updated' => 'user.profile_updated',
        'zrz_user_registered' => 'user.registered',
    ],

    /** Role keys for related-recipient resolution. */
    'related_roles' => [
        'lead_owner',
        'prospect',
        'area_rep',
    ],

    /*
    |--------------------------------------------------------------------------
    | Email branding (Zorzees-compatible layout defaults)
    |--------------------------------------------------------------------------
    | Used by all outbound HTML mail: notifications, drips, FDD delivery, tests.
    | Legacy Zorzees ACF options: logo, bg, menu_active_color, button_color, footer.
    */
    'branding' => [
        'logo_url' => env('FIL_EMAIL_LOGO_URL'),
        'logo_width' => env('FIL_EMAIL_LOGO_WIDTH', '300'),
        'background_color' => env('FIL_EMAIL_BG_COLOR', '#f5f5f5'),
        'brand_color' => env('FIL_EMAIL_BRAND_COLOR', '#53387B'),
        'button_color' => env('FIL_EMAIL_BUTTON_COLOR', '#999999'),
        'footer_text' => env('FIL_EMAIL_FOOTER', 'Powered by FIL'),
        'app_url' => env('APP_URL'),
    ],
];
