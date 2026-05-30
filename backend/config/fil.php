<?php

declare(strict_types=1);

return [
    'ai_service_url' => env('FIL_AI_SERVICE_URL'),
    'embed_allowed_origins' => array_filter(array_map(
        trim(...),
        explode(',', (string) env('FIL_EMBED_ALLOWED_ORIGINS', ''))
    )),
    'embed' => [
        'site_keys' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('FIL_EMBED_SITE_KEYS', 'pk_dev'))
        ))),
    ],
    'documents' => [
        'preview_url_hosts' => array_values(array_filter(array_map(
            trim(...),
            explode(',', (string) env('FIL_DOCUMENT_PREVIEW_URL_HOSTS', ''))
        ))),
    ],
    'recaptcha' => [
        'site_key' => env('FIL_RECAPTCHA_SITE_KEY'),
        'secret_key' => env('FIL_RECAPTCHA_SECRET_KEY'),
    ],
    'legacy' => [
        'dump_path' => env('FIL_LEGACY_DUMP_PATH', '../data/local.sql.gz'),
        'table_prefix' => env('FIL_LEGACY_TABLE_PREFIX', 'vnzokz0zw_9_'),
        'acf_path' => env('FIL_LEGACY_ACF_PATH', base_path('resources/legacy-acf')),
        'role_map' => [
            'administrator' => 'admin',
            'franchiseadmin' => 'franchisor',
            'operations' => 'franchisor',
            'marketing' => 'franchisor',
            'area_rep' => 'area_rep',
            'franchisee' => 'franchisee',
            'storemanager' => 'storemanager',
            'employee' => 'employee',
            'corp-trainer' => 'employee',
            'prospect' => 'prospect',
            'Prospect' => 'prospect',
            'partner' => 'prospect',
            'spouse' => 'prospect',
            'terminated-franchisee' => 'prospect',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Data scope tiers (franchise boundaries)
    |--------------------------------------------------------------------------
    | Replaces legacy Zorzees filterable roles + EP query_filters.
    | Enforced in ResourceScopeService, policies, and GridQueryService.
    */
    'scope' => [
        'tiers' => [
            'unrestricted' => ['admin', 'franchisor', 'lead_owner'],
            'area' => ['area_rep'],
            'store' => ['franchisee', 'storemanager', 'employee'],
        ],
        'leads_blocked_roles' => ['franchisee', 'storemanager', 'employee'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Staff app access
    |--------------------------------------------------------------------------
    | Prospects use the public embed widget only. All other roles need app.access.
    */
    'staff_login_permission' => 'app.access',

    /*
    |--------------------------------------------------------------------------
    | Permissions (Spatie)
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'app.access',
        'leads.view',
        'leads.manage',
        'contacts.view',
        'contacts.manage',
        'stores.view',
        'stores.manage',
        'reports.view',
        'documents.view',
        'fdd.view',
        'fdd.manage',
        'royalties.view',
        'royalties.manage',
        'ach.view',
        'ach.manage',
        'communications.view',
        'communications.manage',
        'ai.use',
        'settings.manage',
        'fields.manage',
    ],

    /*
    |--------------------------------------------------------------------------
    | Role → permissions
    |--------------------------------------------------------------------------
    */
    'role_permissions' => [
        'admin' => '*',
        'franchisor' => [
            'app.access',
            'leads.view',
            'leads.manage',
            'contacts.view',
            'contacts.manage',
            'stores.view',
            'stores.manage',
            'reports.view',
            'documents.view',
            'fdd.view',
            'fdd.manage',
            'royalties.view',
            'royalties.manage',
            'ach.view',
            'ach.manage',
            'communications.view',
            'communications.manage',
            'ai.use',
            'fields.manage',
        ],
        'lead_owner' => [
            'app.access',
            'leads.view',
            'leads.manage',
            'contacts.view',
            'reports.view',
            'documents.view',
            'fdd.view',
            'communications.view',
            'ai.use',
        ],
        'area_rep' => [
            'app.access',
            'leads.view',
            'leads.manage',
            'contacts.view',
            'contacts.manage',
            'stores.view',
            'reports.view',
            'documents.view',
            'fdd.view',
            'communications.view',
            'ai.use',
        ],
        'franchisee' => [
            'app.access',
            'stores.view',
            'contacts.view',
            'documents.view',
        ],
        'storemanager' => [
            'app.access',
            'stores.view',
            'contacts.view',
            'documents.view',
        ],
        'employee' => [
            'app.access',
            'stores.view',
            'contacts.view',
            'documents.view',
        ],
        'prospect' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | SPA navigation (policy / gate gated)
    |--------------------------------------------------------------------------
    | NavigationService resolves each item via Laravel policies or named gates.
    */
    'navigation' => [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'path' => '/', 'gate' => 'accessStaffApp'],
        ['id' => 'history', 'label' => 'Activity', 'path' => '/history', 'permission' => 'app.access'],
        ['id' => 'documents', 'label' => 'Documents', 'path' => '/documents', 'policy' => \App\Domain\Document::class, 'ability' => 'viewAny'],
        ['id' => 'fdd', 'label' => 'FDD', 'path' => '/fdd', 'policy' => \App\Models\Fdd::class, 'ability' => 'viewAny'],
        ['type' => 'section', 'label' => 'Reports'],
        ['id' => 'stores', 'label' => 'My Units', 'path' => '/reports/stores', 'policy' => \App\Models\Store::class, 'ability' => 'viewAny'],
        ['id' => 'contacts', 'label' => 'Contacts', 'path' => '/reports/contacts', 'policy' => \App\Domain\Contact::class, 'ability' => 'viewAny'],
        ['id' => 'leads', 'label' => 'Leads', 'path' => '/reports/leads', 'policy' => \App\Models\Lead::class, 'ability' => 'viewAny'],
        ['type' => 'section', 'label' => 'Finance'],
        ['id' => 'royalties', 'label' => 'Royalties', 'path' => '/reports/royalties', 'gate' => 'viewAnyRoyalty'],
        ['id' => 'ach', 'label' => 'ACH', 'path' => '/reports/ach', 'gate' => 'viewAnyAch'],
        ['id' => 'ai', 'label' => 'Assistant', 'path' => '/ai', 'policy' => \App\Domain\AiAssistant::class, 'ability' => 'access'],
        ['type' => 'section', 'label' => 'Admin'],
        [
            'id' => 'settings',
            'label' => 'Settings',
            'path' => '/settings',
            'permission' => 'app.access',
            'children' => [
                ['id' => 'profile', 'label' => 'My profile', 'path' => '/profile', 'permission' => 'app.access'],
                ['id' => 'settings-notifications', 'label' => 'My notifications', 'path' => '/settings/notifications', 'permission' => 'app.access'],
                ['id' => 'settings-rules', 'label' => 'Notification rules', 'path' => '/settings/notifications/rules', 'permission' => 'settings.manage'],
                ['id' => 'settings-mail', 'label' => 'Mail delivery', 'path' => '/settings/mail', 'permission' => 'settings.manage'],
                ['id' => 'settings-drips', 'label' => 'Drip sequences', 'path' => '/settings/drips', 'permission' => 'settings.manage'],
                ['id' => 'settings-fields', 'label' => 'Custom fields', 'path' => '/settings/fields', 'permission' => 'fields.manage'],
                ['id' => 'settings-widget', 'label' => 'Widget form', 'path' => '/settings/widget', 'permission' => 'fields.manage'],
            ],
        ],
    ],
];
