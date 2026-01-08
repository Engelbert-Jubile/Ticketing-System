<?php

return [
    'two_factor' => env('FEATURE_TWO_FACTOR', false),
    'impersonation' => env('FEATURE_IMPERSONATION', true),
    'ip_restrictions' => env('FEATURE_IP_RESTRICTIONS', true),
    'maintenance_controls' => env('FEATURE_MAINTENANCE_CONTROLS', true),
    'cache_actions' => env('FEATURE_CACHE_ACTIONS', true),
    'rebuild_indexes' => env('FEATURE_REBUILD_INDEXES', true),
    'system_actions_in_production' => env('FEATURE_SYSTEM_ACTIONS_PROD', false),
    'email_verification' => env('FEATURE_EMAIL_VERIFICATION', false),
];
