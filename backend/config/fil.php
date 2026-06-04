<?php

declare(strict_types=1);
use App\Domain\AiAssistant;
use App\Domain\Contact;
use App\Domain\Document;
use App\Models\Fdd;
use App\Models\Lead;
use App\Models\Store;

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
    'widget' => [
        /** Field groups whose lead fields may be added to embed widget forms. */
        'allowed_field_group_keys' => ['applications', 'user'],
        /** Legacy ACF {@code fl-react-app-column-default} → {@code fields.config.widget_eligible}. */
        'eligibility_acf_flag' => 'fl-react-app-column-default',
    ],

    /*
    |--------------------------------------------------------------------------
    | Contacts (User records, excluding prospects)
    |--------------------------------------------------------------------------
    | CRM contacts are users with franchise/staff roles. Prospects are users too
    | (leads, FDD, comms) but never appear in the contacts grid or /contacts API.
    */
    'contact' => [
        'prospect_roles' => ['prospect'],
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
        'notes.manage',
        'todos.view',
        'todos.manage',
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
            'notes.manage',
            'todos.view',
            'todos.manage',
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
            'todos.view',
            'todos.manage',
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
            'todos.view',
            'todos.manage',
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
        ['id' => 'documents', 'label' => 'Documents', 'path' => '/documents', 'policy' => Document::class, 'ability' => 'viewAny'],
        ['id' => 'fdd', 'label' => 'FDD', 'path' => '/fdd', 'policy' => Fdd::class, 'ability' => 'viewAny'],
        ['type' => 'section', 'label' => 'Reports'],
        ['id' => 'stores', 'label' => 'My Units', 'path' => '/reports/stores', 'policy' => Store::class, 'ability' => 'viewAny'],
        ['id' => 'areas', 'label' => 'Areas', 'path' => '/reports/areas', 'policy' => Store::class, 'ability' => 'viewAny'],
        ['id' => 'contacts', 'label' => 'Contacts', 'path' => '/reports/contacts', 'policy' => Contact::class, 'ability' => 'viewAny'],
        ['id' => 'leads', 'label' => 'Leads', 'path' => '/reports/leads', 'policy' => Lead::class, 'ability' => 'viewAny'],
        ['id' => 'closings', 'label' => 'Closings', 'path' => '/reports/closings', 'policy' => Lead::class, 'ability' => 'viewAny'],
        ['type' => 'section', 'label' => 'Finance'],
        ['id' => 'royalties', 'label' => 'Royalties', 'path' => '/reports/royalties', 'gate' => 'viewAnyRoyalty'],
        ['id' => 'ach', 'label' => 'ACH', 'path' => '/reports/ach', 'gate' => 'viewAnyAch'],
        ['id' => 'ai', 'label' => 'Assistant', 'path' => '/ai', 'policy' => AiAssistant::class, 'ability' => 'access'],
        ['type' => 'section', 'label' => 'Admin'],
        [
            'id' => 'notifications',
            'label' => 'Notifications',
            'path' => '/settings/notifications',
            'permission' => 'app.access',
            'children' => [
                ['id' => 'settings-notifications', 'label' => 'My notifications', 'path' => '/settings/notifications', 'permission' => 'app.access'],
                ['id' => 'settings-rules', 'label' => 'Notification rules', 'path' => '/settings/notifications/rules', 'permission' => 'settings.manage'],
                ['id' => 'settings-drips', 'label' => 'Drip sequences', 'path' => '/settings/drips', 'permission' => 'settings.manage'],
            ],
        ],
        [
            'id' => 'settings',
            'label' => 'Settings',
            'path' => '/settings',
            'permission' => 'app.access',
            'children' => [
                ['id' => 'settings-mail', 'label' => 'Mail delivery', 'path' => '/settings/mail', 'permission' => 'settings.manage'],
                ['id' => 'settings-fields', 'label' => 'Custom fields', 'path' => '/settings/fields', 'permission' => 'fields.manage'],
                ['id' => 'settings-widget', 'label' => 'Widget form', 'path' => '/settings/widget', 'permission' => 'fields.manage'],
            ],
        ],
    ],
];
