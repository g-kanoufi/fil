<?php

declare(strict_types=1);
use App\Support\Fields\FieldTypes;

/**
 * Zorzees ACF → FIL field schema parity (PrimeIV site 9).
 *
 * Source of truth for group keys: z-acf-sync/storage/9/group_*.json
 * Run `php artisan legacy:import-acf` after changing bundled JSON or this map.
 */
return [
    /** Legacy post_type (or user_form:edit) → FIL field entity. */
    'post_type_entity' => [
        'application' => 'lead',
        'store' => 'store',
        'franchise_location' => 'store',
        'area' => 'area',
        'organization' => 'organization',
        'user_form:edit' => 'contact',
        'user_form:register' => 'contact',
        'user_form:add' => 'contact',
    ],

    /**
     * Legacy group key → FIL field group.
     *
     * merge_into: attach imported fields to an existing group key (e.g. Applications advanced → applications).
     * import: false skips the entire group (theme, CMS, out-of-scope CPTs).
     */
    'groups' => [
        'group_5654f5590ab60' => ['key' => 'applications', 'title' => 'Applications', 'entity' => 'lead', 'sort_order' => 1, 'import' => true],
        'group_567601fc21316' => ['key' => 'applications', 'title' => 'Applications', 'entity' => 'lead', 'sort_order' => 1, 'import' => true, 'merge_into' => 'applications'],
        'group_6189e128256d8' => ['key' => 'applications', 'title' => 'Applications', 'entity' => 'lead', 'sort_order' => 1, 'import' => true, 'merge_into' => 'applications'],
        'group_565feadf7950b' => ['key' => 'user', 'title' => 'User', 'entity' => 'contact', 'sort_order' => 2, 'import' => true],
        'group_5f70df9415de8' => ['key' => 'user', 'title' => 'User', 'entity' => 'contact', 'sort_order' => 2, 'import' => true, 'merge_into' => 'user'],
        'group_5e55f6ed3094a' => ['key' => 'units', 'title' => 'Units', 'entity' => 'store', 'sort_order' => 10, 'import' => true],
        'group_5f6adcab783f1' => ['key' => 'units', 'title' => 'Units', 'entity' => 'store', 'sort_order' => 10, 'import' => true, 'merge_into' => 'units'],
        'group_570fc6f67d6f6' => ['key' => 'locations', 'title' => 'Locations', 'entity' => 'store', 'sort_order' => 11, 'import' => true],
        'group_56b9dc28339ca' => ['key' => 'areas', 'title' => 'Areas', 'entity' => 'area', 'sort_order' => 20, 'import' => true],
        'group_5717a09892747' => ['key' => 'organizations', 'title' => 'Organizations', 'entity' => 'organization', 'sort_order' => 21, 'import' => true],
        'group_59317c8b85cc7' => ['key' => 'private-notes', 'title' => 'Private notes', 'entity' => 'lead', 'sort_order' => 30, 'import' => true],
        'group_58a1db94b0edc' => ['key' => 'administrative-notes', 'title' => 'Administrative notes', 'entity' => 'lead', 'sort_order' => 31, 'import' => true],
        // Out of FIL MVP scope — never import as CRM custom fields.
        'group_5a1eee3abc04f' => ['import' => false], // React App options
        'group_5624541241c2f' => ['import' => false], // Franchise options
        'group_5621b13996297' => ['import' => false], // FDD CPT (native fdds table)
        'group_644756352226d' => ['import' => false], // Territories plugin
    ],

    /** Never imported — helper keys, grid UI, or derived status fields. */
    'skip_field_keys' => [
        'status_value',
        'status_options',
        'store_status_value',
        'lead_status_column_order',
        'lead_status_sub_nav',
        'lead_progress_column_order',
        'lead_progress_sub_nav',
        'history_table',
        'lead_owner_column_order',
    ],

    /** UI-only ACF types (container fields are walked recursively). */
    'skip_field_types' => [
        'tab',
        'message',
        'accordion',
        'hidden',
    ],

    /** ACF field type → FIL {@see FieldTypes}. */
    'type_map' => [
        'text' => 'text',
        'textarea' => 'textarea',
        'number' => 'number',
        'range' => 'range',
        'email' => 'email',
        'url' => 'url',
        'select' => 'select',
        'radio' => 'select',
        'checkbox' => 'multiselect',
        'true_false' => 'true_false',
        'date_picker' => 'date',
        'date_time_picker' => 'date_time',
        'post_object' => 'relation_one',
        'user' => 'relation_one',
        'wysiwyg' => 'textarea',
        'oembed' => 'url',
        'color_picker' => 'text',
        'repeater' => 'textarea',
        'file' => 'textarea',
        'image' => 'textarea',
        'gallery' => 'textarea',
        'group' => 'textarea',
        'flexible_content' => 'textarea',
        'clone' => 'textarea',
    ],

    /** post_object post_type → FIL relational entity. */
    'post_type_relations' => [
        'application' => 'lead',
        'store' => 'store',
        'area' => 'area',
        'organization' => 'organization',
        'user' => 'user',
        'franchise_location' => 'store',
    ],

    /** Promote legacy postmeta keys to typed entity columns. */
    'tier1_columns' => [
        'lead' => [
            'lead_status' => 'lead_status',
            'lead_stage' => 'lead_stage',
            'lead_fdd_status' => 'lead_fdd_status',
            'lead_temp' => 'lead_temp',
            'lead_source' => 'lead_source',
            'likelihood_to_close' => 'likelihood_to_close',
            'lead_owner' => 'owner_user_id',
        ],
        'store' => [
            'store_status' => 'store_status',
            'spa_id' => 'spa_id',
            'pos_provider' => 'pos_provider',
            'pos_external_id' => 'pos_external_id',
        ],
        'area' => [
            'approval_status' => 'status',
        ],
    ],

    /** Keys that must not appear on lead/application schema (store/location status). */
    'excluded_lead_field_keys' => [
        'status',
        'status_value',
        'status_options',
        'store_status',
        'store_status_value',
        'open',
        'closed',
        'transferred',
        'termed_pre_open',
        'signed_lease_build_out',
        'site_search',
        'loi_negotiations',
        'lease_negotiations',
    ],

    /** Grid filter promotion for tier-1 keys. */
    'filterable_keys' => [
        'lead_status',
        'lead_stage',
        'lead_temp',
        'lead_source',
        'store_status',
    ],
];
