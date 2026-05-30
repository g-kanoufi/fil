<?php

declare(strict_types=1);

/**
 * Schema hints for AI / heuristic grid search interpretation.
 * Filter vocabulary for the FIL PostgreSQL grid API.
 */
return [
    'resources' => [
        'leads' => [
            'label' => 'Franchise applications (leads)',
            'search_hint' => 'Free-text match on application title / prospect name',
            'filters' => [
                'lead_temp' => ['hot', 'warm', 'cold'],
                'lead_fdd_status' => [
                    'active', 'disclosed', 'inactive', 'waiting_period',
                    'new lead', 'sent fdd', 'sent prior fdd', 'viewed fdd page',
                    'in waiting period', 'out of waiting period',
                    'award franchise', 'award area', 'award portfolio',
                ],
                'lead_status' => ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15'],
                'lead_source' => 'free text source slug or label',
                'pipeline_phase' => [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 99],
                'lead_owner' => 'owner display name substring',
            ],
            'sortable' => ['updated_at', 'created_at', 'title', 'pipeline_phase'],
            'examples' => [
                'hot leads in waiting period updated this week',
                'awarded deals sorted by name',
                'applications in FDD review owned by Jane',
            ],
        ],
        'stores' => [
            'label' => 'Franchise stores',
            'search_hint' => 'Match store name',
            'filters' => [
                'store_status' => ['open', 'pending', 'closed', 'in development'],
                'area_id' => 'numeric area id',
                'pos_provider' => 'square, clover, booker, etc.',
            ],
            'sortable' => ['updated_at', 'created_at', 'name'],
            'examples' => ['open stores in phoenix', 'newest stores'],
        ],
        'contacts' => [
            'label' => 'Staff and franchise contacts',
            'search_hint' => 'Match name or email',
            'filters' => [
                'role' => ['admin', 'franchisor', 'lead_owner', 'prospect'],
            ],
            'sortable' => ['updated_at', 'created_at', 'name', 'email'],
            'examples' => ['franchisors with gmail addresses', 'contacts named smith'],
        ],
    ],
];
