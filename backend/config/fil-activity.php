<?php

declare(strict_types=1);

return [
    'categories' => [
        'lead',
        'store',
        'contact',
        'closing',
        'fdd',
        'comm',
        'auth',
        'settings',
        'import',
        'finance',
        'system',
    ],

    'actions' => [
        'created',
        'updated',
        'deleted',
        'transitioned',
        'sent',
        'signed',
        'delivered',
        'failed',
        'login',
        'login_failed',
        'logout',
        'imported',
        'calculated',
        'triggered',
        'converted',
        'viewed',
        'opened',
        'clicked',
        'read',
    ],

    'retention' => [
        'business_hot_days' => (int) env('FIL_ACTIVITY_HOT_DAYS', 90),
        'business_archive_days' => (int) env('FIL_ACTIVITY_ARCHIVE_DAYS', 730),
        'business_purge_after_days' => env('FIL_ACTIVITY_PURGE_AFTER_DAYS') !== null
            ? (int) env('FIL_ACTIVITY_PURGE_AFTER_DAYS')
            : 730,
        'navigation_hot_days' => (int) env('FIL_ACTIVITY_NAV_HOT_DAYS', 30),
        'navigation_purge_days' => (int) env('FIL_ACTIVITY_NAV_PURGE_DAYS', 90),
    ],

    'navigation' => [
        'max_paths_per_request' => 5,
        'allowlist_prefixes' => [
            '/',
            '/history',
            '/reports/',
            '/documents',
            '/fdd',
            '/ai',
            '/profile',
            '/settings',
        ],
        'grid_paths_exact' => [
            '/reports/leads',
            '/reports/stores',
            '/reports/contacts',
            '/reports/closings',
            '/reports/royalties',
            '/reports/ach',
        ],
    ],

    'default_feed_days' => 30,
    'max_page_size' => 50,
    'max_export_rows' => (int) env('FIL_ACTIVITY_MAX_EXPORT_ROWS', 10000),
];
