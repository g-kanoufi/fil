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

    /**
     * Legacy ACF roles longer than document_links.role (32) map to these storage keys.
     * Full role is preserved on documents.extras.legacy_field_role during import.
     */
    'role_short_names' => [
        'finished_photos_group_supporting_photos_for_website' => 'fin_photos_grp_support_web',
        'lease_documents_lease_amendment_extension_options' => 'lease_docs_amend_ext_opts',
        'construction_before_tape_and_texture_completed' => 'constr_bfr_tape_texture_done',
        'construction_after_assembled_laminar_flow_hood' => 'constr_aft_laminar_hood',
        'construction_after_reception_desk_area_photos' => 'constr_aft_recv_desk_photos',
        'finished_photos_group_lead_photo_for_website' => 'fin_photos_grp_lead_web',
        'distributor_documents_distributor_agreements' => 'dist_docs_agreements',
        'site_audit_photos_group_site_audit_photos' => 'site_audit_photos_grp',
        'finished_photos_group_other_final_photos' => 'fin_photos_grp_other_final',
        'construction_after_cryotherapy_completed' => 'constr_aft_cryo_done',
        'construction_before_framing_completed' => 'constr_bfr_framing_done',
        'construction_before_drywall_completed' => 'constr_bfr_drywall_done',
        'construction_construction_drawing_cd' => 'constr_drawing_cd',
        'equipment_documents_equipment_files' => 'equip_docs_files',
        'construction_before_paint_completed' => 'constr_bfr_paint_done',
        'construction_after_compounding_room' => 'constr_aft_compound_room',
        'construction_after_non_member_area' => 'constr_aft_non_member',
        'signage_site_plan_of_the_property' => 'signage_site_plan',
        'construction_after_reception_sign' => 'constr_aft_recv_sign',
        'construction_after_injection_room' => 'constr_aft_inject_room',
    ],
];
