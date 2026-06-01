<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Closing workflow statuses
    |--------------------------------------------------------------------------
    | Keys are stored on closings.status. "next" lists allowed transition targets.
    */
    'statuses' => [
        'pending' => [
            'label' => 'Pending',
            'next' => ['scheduled', 'cancelled'],
        ],
        'scheduled' => [
            'label' => 'Scheduled',
            'next' => ['in_review', 'cancelled'],
        ],
        'in_review' => [
            'label' => 'In review',
            'next' => ['completed', 'pending'],
        ],
        'completed' => [
            'label' => 'Completed',
            'next' => [],
        ],
        'cancelled' => [
            'label' => 'Cancelled',
            'next' => ['pending'],
        ],
    ],

    'max_export_rows' => (int) env('FIL_CLOSINGS_MAX_EXPORT_ROWS', 5000),
];
