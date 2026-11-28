<?php

/*
|--------------------------------------------------------------------------
| Data classification
|--------------------------------------------------------------------------
|
| Every application table and column must be classified here. The
| DataClassification service treats anything that is missing as
| "internal" (fail-closed), and only "safe" columns are eligible for
| future AI/export use.
|
| Classifications:
|   safe       - non-sensitive, may be exported to approved AI services.
|   internal   - operational data. Never exported by default.
|   pii        - personal data. Never exported; redacted in activity logs.
|   credential - passwords, tokens and other secrets. Never exported and
|                never written to activity changes.
|
| Framework/infrastructure tables are excluded explicitly. Excluded
| tables are also never exported to AI services.
|
*/

return [
    'default' => 'internal',

    'excluded_tables' => [
        'cache' => 'Framework cache storage.',
        'cache_locks' => 'Framework cache lock storage.',
        'failed_jobs' => 'Framework queue failure storage.',
        'job_batches' => 'Framework queue batch metadata.',
        'jobs' => 'Framework queue storage.',
        'migrations' => 'Framework migration bookkeeping.',
        'model_has_permissions' => 'Spatie permission package pivot table.',
        'model_has_roles' => 'Spatie permission package pivot table.',
        'permissions' => 'Spatie permission package table.',
        'role_has_permissions' => 'Spatie permission package pivot table.',
        'roles' => 'Spatie permission package table.',
        'sessions' => 'Framework session storage (may contain credentials and PII).',
    ],

    'tables' => [
        'users' => [
            'id' => 'safe',
            'name' => 'pii',
            'mobile' => 'pii',
            'mobile_verified_at' => 'internal',
            'password' => 'credential',
            'status' => 'internal',
            'remember_token' => 'credential',
            'created_at' => 'safe',
            'updated_at' => 'safe',
            'deleted_at' => 'internal',
        ],
        'expert_profiles' => [
            'id' => 'safe',
            'user_id' => 'internal',
            'specialty' => 'internal',
            'license_number' => 'pii',
            'experience_years' => 'safe',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'code_sequences' => [
            'name' => 'internal',
            'last_value' => 'safe',
            'updated_at' => 'safe',
        ],
        'catalog_items' => [
            'id' => 'safe',
            'type' => 'safe',
            'parent_id' => 'safe',
            'parent_key' => 'safe',
            'slug' => 'internal',
            'source_ref' => 'safe',
            'label_fa' => 'internal',
            'label_en' => 'internal',
            'location_context' => 'internal',
            'location_ambiguous' => 'safe',
            'normalized_label' => 'internal',
            'identity_key' => 'internal',
            'status' => 'safe',
            'source' => 'safe',
            'is_common' => 'safe',
            'sort_order' => 'safe',
            'meta' => 'internal',
            'merged_into_id' => 'safe',
            'created_by' => 'internal',
            'reviewed_by' => 'internal',
            'reviewed_at' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'customers' => [
            'id' => 'safe',
            'customer_code' => 'safe',
            'full_name' => 'pii',
            'customer_type' => 'safe',
            'company_name' => 'pii',
            'national_id' => 'pii',
            'mobile' => 'pii',
            'phone' => 'pii',
            'email' => 'pii',
            'province_id' => 'safe',
            'city_id' => 'safe',
            'address' => 'pii',
            'status' => 'safe',
            'registered_via' => 'safe',
            'notes' => 'internal',
            'search_text' => 'internal',
            'created_by' => 'internal',
            'updated_by' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
            'deleted_at' => 'internal',
        ],
        'customer_user' => [
            'id' => 'safe',
            'customer_id' => 'safe',
            'user_id' => 'internal',
            'is_primary' => 'safe',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'greenhouses' => [
            'id' => 'safe',
            'greenhouse_code' => 'safe',
            'customer_id' => 'safe',
            'responsible_expert_id' => 'internal',
            'name' => 'internal',
            'province_id' => 'safe',
            'city_id' => 'safe',
            'address' => 'pii',
            'latitude' => 'pii',
            'longitude' => 'pii',
            'altitude_m' => 'safe',
            'area_m2' => 'safe',
            'structure_type_id' => 'safe',
            'cultivation_type_id' => 'safe',
            'commissioned_on' => 'safe',
            'status' => 'safe',
            'notes' => 'internal',
            'completeness_percent' => 'safe',
            'search_text' => 'internal',
            'created_by' => 'internal',
            'updated_by' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
            'deleted_at' => 'internal',
        ],
        'greenhouse_options' => [
            'greenhouse_id' => 'safe',
            'catalog_item_id' => 'safe',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'zones' => [
            'id' => 'safe',
            'greenhouse_id' => 'safe',
            'code' => 'safe',
            'name' => 'internal',
            'zone_type_id' => 'safe',
            'area_m2' => 'safe',
            'capacity_plants' => 'safe',
            'status' => 'safe',
            'notes' => 'internal',
            'created_by' => 'internal',
            'updated_by' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
            'deleted_at' => 'internal',
        ],
        'zone_equipment' => [
            'id' => 'safe',
            'zone_id' => 'safe',
            'equipment_id' => 'safe',
            'quantity' => 'safe',
            'notes' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'cultivation_cycles' => [
            'id' => 'safe',
            'cycle_code' => 'safe',
            'greenhouse_id' => 'safe',
            'zone_id' => 'safe',
            'crop_id' => 'safe',
            'variety_id' => 'safe',
            'substrate_id' => 'safe',
            'substrate_details' => 'internal',
            'planting_date' => 'safe',
            'expected_harvest_date' => 'safe',
            'harvest_started_on' => 'safe',
            'harvest_ended_on' => 'safe',
            'plant_count' => 'safe',
            'planted_area_m2' => 'safe',
            'status' => 'safe',
            'total_yield_kg' => 'safe',
            'notes' => 'internal',
            'created_by' => 'internal',
            'updated_by' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
            'deleted_at' => 'internal',
        ],
        'correction_requests' => [
            'id' => 'safe',
            'greenhouse_id' => 'safe',
            'cultivation_cycle_id' => 'safe',
            'requested_by' => 'internal',
            'details' => 'internal',
            'status' => 'safe',
            'resolved_by' => 'internal',
            'resolved_at' => 'internal',
            'resolution' => 'internal',
            'created_at' => 'safe',
            'updated_at' => 'safe',
        ],
        'activities' => [
            'id' => 'safe',
            'occurred_at' => 'safe',
            'actor_id' => 'internal',
            'actor_role' => 'internal',
            'event' => 'safe',
            'subject_type' => 'safe',
            'subject_id' => 'safe',
            'customer_id' => 'safe',
            'greenhouse_id' => 'safe',
            'description' => 'internal',
            'changes' => 'internal',
            'ip_address' => 'pii',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Activity change keys
    |----------------------------------------------------------------------
    |
    | Activity payloads are not always plain table columns. These keys are
    | used by the logger for nested structures. "container" means recurse
    | into the value and redact its leaves; unknown keys fail closed to
    | "internal".
    |
    */
    'activity_change_keys' => [
        'old' => 'container',
        'new' => 'container',
        'options' => 'safe',
        'equipment' => 'container',
        'merge' => 'container',
        'id' => 'safe',
        'equipment_id' => 'safe',
        'quantity' => 'safe',
        'slug' => 'internal',
        'catalog_item_id' => 'safe',
        'notes' => 'internal',
    ],

    'credential_keys' => [
        'api_key',
        'api_token',
        'authorization',
        'otp',
        'otp_code',
        'password',
        'password_confirmation',
        'remember_token',
        'secret',
        'token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ],

    'sensitive_keys' => [
        'address',
        'company_name',
        'email',
        'full_name',
        'ip_address',
        'latitude',
        'license_number',
        'longitude',
        'mobile',
        'national_id',
        'phone',
    ],
];
