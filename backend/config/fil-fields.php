<?php

declare(strict_types=1);
use App\Services\Fields\SystemFieldService;
use App\Support\Fields\FieldChoiceCatalog;

/**
 * FIL system fields — tier-1 columns with admin-editable choice catalogs.
 *
 * Values stored on entities remain opaque strings; labels and behavior (closed,
 * pipeline hints, categories) live in fields.config.choices and can be changed
 * in the Fields admin UI without code deploys.
 *
 * {@see FieldChoiceCatalog}
 * {@see SystemFieldService}
 */
return [
    /**
     * Registered system fields per entity. Importer and seeder ensure these exist;
     * admins may edit labels/choices but not delete system keys via API (future guard).
     *
     * @var array<string, array<string, array{role?: string, filterable?: bool}>>
     */
    'system_keys' => [
        'lead' => [
            'lead_status' => ['role' => 'application_status', 'filterable' => true],
            'lead_stage' => ['role' => 'application_stage', 'filterable' => true],
            'lead_temp' => ['role' => 'temperature', 'filterable' => true],
            'lead_fdd_status' => ['role' => 'fdd_status'],
            'lead_source' => ['role' => 'source', 'filterable' => true],
        ],
        'store' => [
            'store_status' => ['role' => 'unit_status', 'filterable' => true],
        ],
    ],

    /**
     * Default field group per entity for system tier-1 fields.
     *
     * @var array<string, string>
     */
    'group_keys' => [
        'lead' => 'applications',
        'store' => 'units',
    ],

    /**
     * plus legacy aliases so imported Zorzees numeric keys still resolve.
     *
     * @var array<string, array<string, array<string, mixed>>>
     */
    'defaults' => [
        'lead' => [
            'lead_status' => [
                'name' => 'Application status',
                'type' => 'select',
                'choices' => [
                    ['value' => 'new_lead', 'label' => 'New Lead', 'aliases' => ['1'], 'meta' => ['category' => 'active', 'sort' => 10]],
                    ['value' => 'sent_text', 'label' => 'Sent Text Message', 'aliases' => ['2'], 'meta' => ['category' => 'active', 'sort' => 20]],
                    ['value' => 'call_1', 'label' => '1st Call — Left Message', 'aliases' => ['3'], 'meta' => ['category' => 'active', 'sort' => 30]],
                    ['value' => 'call_2', 'label' => '2nd Call — Left Message', 'aliases' => ['4'], 'meta' => ['category' => 'active', 'sort' => 40]],
                    ['value' => 'call_3', 'label' => '3rd Call — Left Message', 'aliases' => ['5'], 'meta' => ['category' => 'active', 'sort' => 50]],
                    ['value' => 'engaged', 'label' => 'Spoke with Prospect', 'aliases' => ['6'], 'meta' => ['category' => 'active', 'pipeline_phase' => 3, 'sort' => 60]],
                    ['value' => 'transfer_sale', 'label' => 'Transfer Sale', 'aliases' => ['7'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 70]],
                    ['value' => 'international', 'label' => 'International Leads', 'aliases' => ['8'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 80]],
                    ['value' => 'inactive', 'label' => 'Inactive', 'aliases' => ['9'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 90]],
                    ['value' => 'not_qualified', 'label' => 'Not Qualified', 'aliases' => ['10'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 100]],
                    ['value' => 'dead_deal', 'label' => 'Dead Deal', 'aliases' => ['11'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 110]],
                    ['value' => 'close_application', 'label' => 'Close Application', 'aliases' => ['12'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 120]],
                    ['value' => 'deny_application', 'label' => 'Deny Application', 'aliases' => ['13'], 'meta' => ['category' => 'closed', 'closed' => true, 'pipeline_phase' => 99, 'sort' => 130]],
                    ['value' => 'awarded_franchise', 'label' => 'Award Franchise', 'aliases' => ['14'], 'meta' => ['category' => 'won', 'pipeline_phase' => 10, 'sort' => 140]],
                    ['value' => 'awarded_area', 'label' => 'Award Area', 'aliases' => ['15'], 'meta' => ['category' => 'won', 'pipeline_phase' => 10, 'sort' => 150]],
                ],
            ],
            'lead_stage' => [
                'name' => 'Application stage',
                'type' => 'select',
                'choices' => [
                    ['value' => 'pre_disclosure', 'label' => 'Pre-Disclosure', 'aliases' => ['1'], 'meta' => ['sort' => 10]],
                    ['value' => 'disclosed', 'label' => 'Disclosed', 'aliases' => ['2'], 'meta' => ['sort' => 20]],
                    ['value' => 'finalized', 'label' => 'Finalized', 'aliases' => ['3'], 'meta' => ['sort' => 30]],
                ],
            ],
            'lead_temp' => [
                'name' => 'Temperature',
                'type' => 'select',
                'choices' => [
                    ['value' => 'cold', 'label' => 'Cold', 'aliases' => ['Cold'], 'meta' => ['sort' => 10]],
                    ['value' => 'warm', 'label' => 'Warm', 'aliases' => ['Warm'], 'meta' => ['sort' => 20]],
                    ['value' => 'hot', 'label' => 'Hot', 'aliases' => ['Hot'], 'meta' => ['sort' => 30]],
                ],
            ],
            'lead_fdd_status' => [
                'name' => 'FDD status',
                'type' => 'select',
                'choices' => [
                    ['value' => 'new_lead', 'label' => 'New Lead', 'meta' => ['pipeline_phase' => 1, 'sort' => 10]],
                    ['value' => 'active', 'label' => 'Active', 'meta' => ['category' => 'active', 'sort' => 20]],
                    ['value' => 'disclosed', 'label' => 'FDD Sent', 'aliases' => ['sent fdd'], 'meta' => ['pipeline_phase' => 5, 'sort' => 30]],
                    ['value' => 'waiting_period', 'label' => 'In Waiting Period', 'aliases' => ['in waiting period'], 'meta' => ['pipeline_phase' => 8, 'sort' => 40]],
                    ['value' => 'out_of_waiting_period', 'label' => 'Out of Waiting Period', 'meta' => ['pipeline_phase' => 9, 'sort' => 50]],
                    ['value' => 'inactive', 'label' => 'Inactive', 'meta' => ['closed' => true, 'pipeline_phase' => 99, 'sort' => 60]],
                ],
            ],
            'lead_source' => [
                'name' => 'Lead source',
                'type' => 'text',
            ],
        ],
        'store' => [
            'store_status' => [
                'name' => 'Unit status',
                'type' => 'select',
                'choices' => [
                    ['value' => 'pending', 'label' => 'Pending', 'meta' => ['category' => 'development', 'sort' => 10]],
                    ['value' => 'in_development', 'label' => 'In Development', 'meta' => ['category' => 'development', 'sort' => 20]],
                    ['value' => 'open', 'label' => 'Open', 'meta' => ['category' => 'operating', 'sort' => 30]],
                    ['value' => 'closed', 'label' => 'Closed', 'meta' => ['category' => 'closed', 'closed' => true, 'sort' => 40]],
                ],
            ],
        ],
    ],
];
