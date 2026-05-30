<?php

declare(strict_types=1);

/**
 * UI access catalog — staff app domains and menu visibility.
 *
 * Domains map to legacy adminimize_react_options keys:
 * - grid_tabs, grid_leads, grid_stores, grid_contacts
 * - nav_admin, nav_stores, nav_contacts
 * - resources, features
 *
 * @see docs/ACCESS.md
 */
return [
    'domains' => [
        'grid_tabs' => [
            ['key' => 'export_csv', 'label' => 'Export CSV', 'item_type' => 'tab'],
            ['key' => 'bulk_actions', 'label' => 'Bulk actions', 'item_type' => 'tab'],
        ],
        'grid_leads' => [
            ['key' => 'lead_status', 'label' => 'Lead status', 'item_type' => 'menu'],
            ['key' => 'lead_owner', 'label' => 'Lead owner', 'item_type' => 'menu'],
            ['key' => 'lead_temp', 'label' => 'Lead temperature', 'item_type' => 'menu'],
            ['key' => 'lead_source', 'label' => 'Lead source', 'item_type' => 'menu'],
            ['key' => 'leads_active', 'label' => 'Active leads', 'item_type' => 'submenu', 'parent_key' => 'lead_status'],
            ['key' => 'leads_awarded_deals', 'label' => 'Awarded deals', 'item_type' => 'submenu', 'parent_key' => 'lead_status'],
        ],
        'grid_stores' => [
            ['key' => 'store_status', 'label' => 'Unit statuses', 'item_type' => 'menu'],
            ['key' => 'store_area', 'label' => 'Areas', 'item_type' => 'menu'],
        ],
        'grid_contacts' => [
            ['key' => 'contact_role', 'label' => 'Contact roles', 'item_type' => 'menu'],
        ],
        'nav_admin' => [
            ['key' => 'nav_admin_news', 'label' => 'News', 'item_type' => 'nav'],
            ['key' => 'nav_admin_reports', 'label' => 'Reports', 'item_type' => 'nav'],
            ['key' => 'nav_admin_documents', 'label' => 'Documents', 'item_type' => 'nav'],
        ],
        'nav_stores' => [
            ['key' => 'nav_stores_store_info', 'label' => 'Store info', 'item_type' => 'nav'],
            ['key' => 'nav_stores_royalties', 'label' => 'Royalties', 'item_type' => 'nav'],
            ['key' => 'nav_stores_ach', 'label' => 'ACH', 'item_type' => 'nav'],
        ],
        'nav_contacts' => [
            ['key' => 'nav_contact_contact_info', 'label' => 'Contact info', 'item_type' => 'nav'],
            ['key' => 'nav_contact_applications', 'label' => 'Applications', 'item_type' => 'nav'],
        ],
        'features' => [
            ['key' => 'detail_panel', 'label' => 'Detail panel', 'item_type' => 'feature'],
            ['key' => 'zai_assistant', 'label' => 'AI assistant', 'item_type' => 'feature'],
        ],
    ],

    /*
    | Default allowed=false entries per role (Adminimize stores *disabled* lists;
    | FIL stores explicit grants — seeder sets allowed=false for these keys).
    */
    'default_denied' => [
        'lead_owner' => [
            'grid_tabs' => ['export_csv', 'bulk_actions'],
            'grid_stores' => ['store_area'],
            'nav_stores' => ['nav_stores_royalties', 'nav_stores_ach'],
            'nav_admin' => ['nav_admin_reports'],
            'features' => [],
        ],
        'area_rep' => [
            'nav_admin' => ['nav_admin_reports'],
        ],
        'franchisee' => [
            'grid_tabs' => ['export_csv', 'bulk_actions'],
            'grid_leads' => ['lead_status', 'lead_owner', 'lead_temp', 'lead_source'],
            'nav_stores' => ['nav_stores_royalties', 'nav_stores_ach'],
        ],
        'storemanager' => [
            'grid_tabs' => ['export_csv'],
            'nav_stores' => ['nav_stores_royalties', 'nav_stores_ach'],
        ],
        'employee' => [
            'grid_tabs' => ['export_csv', 'bulk_actions'],
            'nav_stores' => ['nav_stores_royalties', 'nav_stores_ach'],
        ],
        'franchisor' => [
            'grid_tabs' => [],
            'grid_leads' => [],
            'grid_stores' => [],
            'grid_contacts' => [],
            'nav_admin' => [],
            'nav_stores' => [],
            'nav_contacts' => [],
            'features' => [],
        ],
    ],

    'note_entities' => ['lead', 'store', 'area', 'contact'],

    'default_note_grants' => [
        'admin' => ['lead' => [true, true], 'store' => [true, true], 'area' => [true, true], 'contact' => [true, true]],
        'franchisor' => ['lead' => [true, true], 'store' => [true, true], 'area' => [true, false], 'contact' => [true, false]],
        'lead_owner' => ['lead' => [true, false], 'store' => [false, false], 'area' => [false, false], 'contact' => [true, false]],
    ],
];
