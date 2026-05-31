<?php

declare(strict_types=1);

/**
 * Streamlined franchise application pipeline derived from legacy
 * `lead_progress` (13 steps) and `lead_status` / `lead_fdd_status` fields.
 *
 * Storage: leads.pipeline_phase (tinyint key below).
 * Display: use LeadPipelineCatalog for labels — never show raw numbers in UI.
 */
return [
    /*
    |--------------------------------------------------------------------------
    | Pipeline phases (flattened from legacy lead_progress 1–13)
    |--------------------------------------------------------------------------
    */
    'phases' => [
        1 => ['label' => 'Intake', 'description' => 'Short form submitted'],
        2 => ['label' => 'Outreach', 'description' => 'Initial contact attempted'],
        3 => ['label' => 'Engaged', 'description' => 'Spoke with prospect'],
        4 => ['label' => 'Qualified', 'description' => 'Long form completed'],
        5 => ['label' => 'FDD Disclosed', 'description' => 'FDD sent to prospect'],
        6 => ['label' => 'FDD Review', 'description' => 'Prospect reviewing FDD materials'],
        7 => ['label' => 'FDD Signed', 'description' => 'FDD receipt signed'],
        8 => ['label' => 'Waiting Period', 'description' => 'Mandatory waiting period in progress'],
        9 => ['label' => 'Ready to Award', 'description' => 'Waiting period complete'],
        10 => ['label' => 'Awarded', 'description' => 'Deal awarded'],
        99 => ['label' => 'Closed', 'description' => 'Inactive or disqualified'],
    ],

    /** @var list<int> */
    'valid_phases' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 99],

    /** Legacy ACF lead_progress value → streamlined pipeline_phase */
    'legacy_progress_map' => [
        1 => 1,   // Short Form date entered
        2 => 2,   // Reached Out
        3 => 3,   // Spoke with Prospect
        4 => 4,   // Long Form date entered
        5 => 5,   // FDD Sent
        6 => 6,   // Viewed FDD Intro
        7 => 6,   // Signed NDA
        8 => 6,   // Viewed FDD
        9 => 6,   // Viewed Embedded FDD
        10 => 7,  // Signed FDD Receipt
        11 => 8,  // In Waiting Period
        12 => 9,  // Out of Waiting Period
        13 => 10, // Awarded Deal
    ],

    /*
    |--------------------------------------------------------------------------
    | Application status labels (lead_status + lead_fdd_status unified)
    |--------------------------------------------------------------------------
    */
    'lead_status_labels' => [
        '1' => 'New Lead',
        '2' => 'Sent Text Message',
        '3' => '1st Call — Left Message',
        '4' => '2nd Call — Left Message',
        '5' => '3rd Call — Left Message',
        '6' => 'Spoke with Prospect',
        '7' => 'Transfer Sale',
        '8' => 'International Leads',
        '9' => 'Inactive',
        '10' => 'Not Qualified',
        '11' => 'Dead Deal',
        '12' => 'Close Application',
        '13' => 'Deny Application',
        '14' => 'Award Franchise (Agreement Signed)',
        '15' => 'Award Area (Master Agreement Signed)',
    ],

    'fdd_status_labels' => [
        'active' => 'Active',
        'disclosed' => 'FDD Sent',
        'waiting_period' => 'In Waiting Period',
        'inactive' => 'Inactive',
        'new lead' => 'New Lead',
        'sent fdd' => 'FDD Sent',
        'sent prior fdd' => 'Sent Prior FDD',
        'viewed fdd page' => 'Viewed FDD Page',
        'viewed fdd intro' => 'Viewed FDD Intro',
        'viewed intro' => 'Viewed FDD Intro',
        'in waiting period' => 'In Waiting Period',
        'out of waiting period' => 'Out of Waiting Period',
        'sent text message' => 'Sent Text Message',
        'spoke with prospect' => 'Spoke with Prospect',
    ],

    /** Statuses that map to pipeline phase 99 (closed) */
    'closed_status_keys' => [
        'inactive',
        'transfer sale',
        'international leads',
        'not qualified',
        'dead deal',
        'close application',
        'deny application',
        '7', '8', '9', '10', '11', '12', '13',
    ],

    /** FDD / award statuses → pipeline phase inference */
    'status_phase_hints' => [
        'disclosed' => 5,
        'sent fdd' => 5,
        'sent prior fdd' => 5,
        'viewed fdd page' => 6,
        'viewed fdd intro' => 6,
        'viewed intro' => 6,
        'waiting_period' => 8,
        'in waiting period' => 8,
        'out of waiting period' => 9,
        'award franchise' => 10,
        'award area' => 10,
        'award portfolio' => 10,
    ],
];
