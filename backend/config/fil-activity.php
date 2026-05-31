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
    ],

    'retention' => [
        'hot_days' => (int) env('FIL_ACTIVITY_HOT_DAYS', 90),
        'archive_days' => (int) env('FIL_ACTIVITY_ARCHIVE_DAYS', 730),
        'purge_after_days' => env('FIL_ACTIVITY_PURGE_AFTER_DAYS') !== null
            ? (int) env('FIL_ACTIVITY_PURGE_AFTER_DAYS')
            : 730,
    ],

    'default_feed_days' => 30,
    'max_page_size' => 50,
    'max_export_rows' => (int) env('FIL_ACTIVITY_MAX_EXPORT_ROWS', 10000),
];
