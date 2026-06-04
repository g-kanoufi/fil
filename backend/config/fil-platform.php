<?php

declare(strict_types=1);

/**
 * Franchise Intelligence Platform — four lifecycle stages (Zorzees parity).
 *
 * Used for staff nav grouping, dashboard widgets, activity tagging, and roadmap tracking.
 * See docs/PLAN_3_STAGE_PLATFORM.md.
 */
return [
    'stages' => [
        'find_sell' => [
            'label' => 'Find + Sell',
            'description' => 'Prospect intake, pipeline, FDD disclosure, and engagement.',
            'sort' => 1,
            'pipeline_phases' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10],
            'navigation_ids' => ['leads', 'fdd', 'history', 'ai'],
        ],
        'build_open' => [
            'label' => 'Build + Open',
            'description' => 'Award, closings, agreements, and store opening.',
            'sort' => 2,
            'pipeline_phases' => [10],
            'navigation_ids' => ['closings', 'stores'],
        ],
        'operate_inspect' => [
            'label' => 'Operate + Inspect',
            'description' => 'Unit operations, contacts, documents, and compliance.',
            'sort' => 3,
            'pipeline_phases' => [],
            'navigation_ids' => ['stores', 'contacts', 'documents'],
        ],
        'grow_earn' => [
            'label' => 'Grow + Earn',
            'description' => 'Royalties, ACH collection, and revenue performance.',
            'sort' => 4,
            'pipeline_phases' => [],
            'navigation_ids' => ['royalties', 'ach'],
        ],
    ],

    'portal' => [
        'enabled' => filter_var(env('FIL_PORTAL_ENABLED', true), FILTER_VALIDATE_BOOL),
        'setup_token_ttl_hours' => (int) env('FIL_PORTAL_SETUP_TTL_HOURS', 72),
        'app_url' => rtrim((string) env('FIL_PORTAL_APP_URL', env('APP_URL', 'http://localhost:8000')), '/'),
        'redirect_after_intake' => filter_var(env('FIL_PORTAL_REDIRECT_AFTER_INTAKE', true), FILTER_VALIDATE_BOOL),
        /** Field groups exposed on the prospect long-form portal. */
        'field_group_keys' => ['applications'],
        /** Field keys never shown to prospects (staff-only). */
        'exclude_field_keys' => [
            'lead_owner',
            'lead_temp',
            'likelihood_to_close',
        ],
        /** When set (date field), pipeline advances to phase 4 (Qualified). */
        'completion_field_key' => 'long_form_complete_date',
        'redirect_path' => '/portal/setup',
    ],

    'esign' => [
        /** local | sandbox | dropbox_sign */
        'driver' => env('FIL_ESIGN_DRIVER', 'local'),
        'dropbox_sign' => [
            'api_key' => env('FIL_DROPBOX_SIGN_API_KEY'),
            'client_id' => env('FIL_DROPBOX_SIGN_CLIENT_ID'),
        ],
    ],

    'operate_inspect' => [
        'inspection_lookahead_days' => (int) env('FIL_INSPECTION_LOOKAHEAD_DAYS', 14),
        'inspection_lookback_days' => (int) env('FIL_INSPECTION_LOOKBACK_DAYS', 7),
    ],

    'grow_earn' => [
        /** See config/fil-royalties.php — enable on staging after sandbox verify. */
        'royalty_calc_job_env' => 'FIL_ENABLE_ROYALTY_CALC_JOB',
        'ach_collection_env' => 'FIL_ENABLE_ACH_COLLECTION',
        'pos' => [
            /** square | sandbox (falls back to sandbox client when Square creds missing) */
            'default_provider' => env('FIL_POS_DEFAULT_PROVIDER', 'square'),
        ],
    ],

    'opening' => [
        /** Checklist item keys seeded for BUILD + OPEN (Plan 3 Phase B). */
        'default_checklist_keys' => [
            'site_selected',
            'lease_signed',
            'buildout_started',
            'certificate_of_occupancy',
            'grand_opening_scheduled',
        ],
        'default_checklist_labels' => [
            'site_selected' => 'Site selected',
            'lease_signed' => 'Lease signed',
            'buildout_started' => 'Build-out started',
            'certificate_of_occupancy' => 'Certificate of occupancy',
            'grand_opening_scheduled' => 'Grand opening scheduled',
        ],
    ],
];
