<?php

declare(strict_types=1);

return [
    'enabled' => (bool) env('FIL_DOCUMENTS_BROWSER_ENABLED', true),

    /** Entity sections shown in the documents browser (legacy parity). */
    'enabled_entities' => ['store', 'lead', 'user', 'location'],

    /**
     * Document type roles included in browser rows.
     * Leave empty for an entity (see entity_document_types) to include all imported roles.
     */
    'document_types' => [
        'doctors_license',
        'medical_certification',
        'fdd_receipt',
        'area_fdd_receipt',
    ],

    /** Per-entity role filters; null means include all roles for that entity. */
    'entity_document_types' => [
        'store' => null,
        'lead' => null,
        'user' => null,
        'location' => null,
    ],

    /** ACF field group JSON used to discover file meta patterns during legacy import. */
    'acf_field_groups' => [
        'store' => [
            'resources/legacy-acf/group_5e55f6ed3094a.json',
            'resources/legacy-acf/group_5f6adcab783f1.json',
        ],
        'franchise_location' => 'resources/legacy-acf/group_570fc6f67d6f6.json',
    ],

    /** Optional path to legacy uploads for binary copy during import. */
    'legacy_uploads_path' => env('FIL_LEGACY_UPLOADS_PATH'),

    'labels' => [
        'doctors_license' => 'Doctors License',
        'medical_certification' => 'Medical Certification',
        'fdd_receipt' => 'Unit FDD Receipt',
        'area_fdd_receipt' => 'Area FDD Receipt',
    ],
];
