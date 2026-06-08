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
    /** Skip unregistered postmeta orphans during import (delegates to LegacyExtrasKeyResolver). */
    'meta_hygiene' => env('FIL_LEGACY_META_HYGIENE', true),

    /** Legacy post_type (or user_form:edit) → FIL field entity. */
    'post_type_entity' => [
        'application' => 'lead',
        'store' => 'store',
        'franchise_location' => 'store',
        'area' => 'area',
        'organization' => 'organization',
        'user' => 'contact',
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
        'group_5c3e17a843a14' => ['import' => false], // Theme - Validators (CMS; not CRM)
        'group_5b154cc14535e' => ['import' => false], // Theme - Featured Image Caption
        'group_5e33f7593eab2' => ['import' => false], // Theme - Featured Images Slider
        'group_5b1161b9bead4' => ['import' => false], // Theme - Slider
        'group_575586713f2ff' => ['import' => false], // Theme - faq
        'group_574fffefa31f8' => ['import' => false], // Theme - menu
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
        'royalties_group',
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
        'repeater' => 'repeater',
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
        'contact' => [
            'first_name_user' => 'first_name',
            'last_name_user' => 'last_name',
            'email_address' => 'email',
            'mobile_phone' => 'phone',
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

    /** Legacy postmeta keys → FIL field keys (hyphen/legacy naming). */
    'extras_key_aliases' => [
        'long-term_drip_start_date' => 'long_term_drip_start_date',
    ],

    /** Internal WP/Zorzees keys dropped from extras during drain (not promoted). */
    'extras_discard_keys' => [
        'zrz_check_updated_metas',
        'react_lead_status',
        'store_status_value',
        'pos_location_id',
        'long_form_completed',
        'interested_in_similar_concepts',
        'eligible_viewed_fdd_page',
        'years_employed',
        'local_charities',
        'local_sponsors',
        'local_groups',
        'administrative_notes',
        'square_access_token',
    ],

    /** Prefixes dropped from extras when no FIL field exists (legacy-only UI keys). */
    'extras_discard_prefixes' => [
        'pre_employees_repeater',
        'administrative_notes_',
        'custom_fees_table_royalty_fee_schedule',
        'store_photos',
        'finished_photos_group',
        'franchise_fees',
        'new_store_open_deadline',
        'new_term_end_date',
        'franchise_agreement',
        'store_managers_repeater',
        'employees_repeater',
        'contact_group',
        '_checklist_',
        'checklist_',
        '1_checklist',
    ],
    'extras_discard_keys_by_entity' => [
        'lead' => [
            'org_shareholders',
            'created_by',
            'note',
            'phone',
            'referring_franchise_consultant',
            'just_signed_fdd',
            'assets_group',
            'liabilities_group',
            'source_of_income',
        ],
    ],

    /** Field keys registered on multiple entities (legacy shared postmeta). */
    'shared_field_keys' => [
        'wp_user' => ['lead', 'organization'],
    ],

    /**
     * Promote extras into a related model column (e.g. applicant email → users.email).
     *
     * @var array<string, array<string, array{relation: string, column: string}>>
     */
    'extras_relation_columns' => [
        'lead' => [
            'email' => ['relation' => 'prospect_user_id', 'column' => 'email'],
        ],
    ],

    /**
     * Documented out-of-scope legacy meta (reported by legacy:mapping-gaps).
     * Keys still in extras_discard_prefixes are drained silently; notes explain why.
     */
    'out_of_scope' => [
        'store_photos' => 'Store photo galleries — WP attachment IDs for public website; not FIL CRM MVP',
        'finished_photos_group' => 'Marketing / website photo set — attachment IDs; not FIL CRM MVP',
        'lead_photo_for_website' => 'Public website hero photo — attachment ID',
        'website_card_photo' => 'Public website card photo — attachment ID',
        'supporting_photos_for_website' => 'Public website gallery — attachment IDs',
        '360_degree_interior_photo' => '360° tour embed — attachment / oembed',
        'other_final_photos' => 'Internal marketing photos — attachment IDs',
        'shell_building_photos' => 'Construction milestone photos — attachment IDs',
        'under_construction_photos' => 'Construction milestone photos — attachment IDs',
        'site_audit_photos' => 'Site audit photo gallery — attachment IDs',
        'demo_photos' => 'Demo day photos — attachment IDs',
        'reception_desk_area_photos' => 'Reception area photos — attachment IDs',
        'checklist_' => 'Legacy checklist plugin flattened keys — use nso_checklist_embed field only',
        '_checklist_' => 'Legacy checklist plugin internal keys',
        '1_checklist' => 'Legacy checklist row keys',
        'unit_panel_photo' => 'Unit admin panel photo — attachment ID',
        'area_website' => 'Area website photos — attachment IDs; public site scope',
        'square_access_token' => 'Legacy POS OAuth secret — rotate in Square; not stored in FIL',
        'royalties_group' => 'Legacy royalties UI table rows — use royalty_periods import',
        'history_table' => 'Legacy inline history grid — use activity timeline instead',
        'private_notes' => 'Legacy private-notes repeater on store — import via lead notes / activity',
        'contact_group' => 'Legacy flat contact fields — superseded by prospect user + lead columns',
    ],
];
