<?php

declare(strict_types=1);

use App\Domain\Contact;
use App\Models\Lead;
use App\Models\Store;
use App\Models\User;

return [
    'resources' => [
        'leads' => [
            'model' => Lead::class,
            'table' => 'leads',
            'policy' => 'viewAny',
            'default_status' => 'active',
            'search_columns' => ['title'],
            'filterable' => [
                'lead_status',
                'lead_stage',
                'lead_fdd_status',
                'lead_temp',
                'lead_source',
                'pipeline_phase',
                'owner_user_id',
                'lead_owner',
                'area_id',
                'interest_region_id',
            ],
            'sortable' => ['updated_at', 'created_at', 'title', 'pipeline_phase'],
            'default_sort' => ['field' => 'updated_at', 'direction' => 'desc'],
            'aggregations' => [
                'meta.lead_status' => ['column' => 'lead_status', 'type' => 'lead_application_status'],
                'meta.lead_owner' => ['column' => 'owner_user_id', 'type' => 'owner_name'],
                'meta.likelihood_to_close' => ['column' => 'likelihood_to_close', 'type' => 'terms'],
                'meta.lead_temp' => ['column' => 'lead_temp', 'type' => 'terms'],
                'meta.lead_source' => ['column' => 'lead_source', 'type' => 'terms'],
                'meta.interest_region' => ['column' => 'interest_region_id', 'type' => 'interest_region_name'],
            ],
        ],
        'stores' => [
            'model' => Store::class,
            'table' => 'stores',
            'policy' => 'viewAny',
            'default_status' => 'active',
            'search_columns' => ['name'],
            'filterable' => ['store_status', 'status', 'area_id', 'pos_provider'],
            'sortable' => ['updated_at', 'created_at', 'name'],
            'default_sort' => ['field' => 'updated_at', 'direction' => 'desc'],
            'aggregations' => [
                'meta.stores' => ['column' => 'store_status', 'type' => 'terms'],
                'meta.areas' => ['column' => 'area_id', 'type' => 'area_name'],
            ],
        ],
        'contacts' => [
            'model' => User::class,
            'policy_model' => Contact::class,
            'table' => 'users',
            'policy' => 'viewAny',
            'skip_status_filter' => true,
            'search_columns' => ['name', 'first_name', 'last_name', 'email'],
            'filterable' => ['role'],
            'sortable' => ['updated_at', 'created_at', 'name', 'email'],
            'default_sort' => ['field' => 'updated_at', 'direction' => 'desc'],
            'aggregations' => [
                'meta.contacts' => ['column' => 'roles.name', 'type' => 'role_name'],
            ],
        ],
    ],
];
