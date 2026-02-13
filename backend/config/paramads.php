<?php

return [
    /*
    |--------------------------------------------------------------------------
    | ParamAds Configuration
    |--------------------------------------------------------------------------
    */

    'meta' => [
        'app_id' => env('META_APP_ID'),
        'app_secret' => env('META_APP_SECRET'),
        'redirect_uri' => env('META_REDIRECT_URI'),
        'api_version' => env('META_API_VERSION', 'v18.0'),
    ],

    'google_ads' => [
        'client_id' => env('GOOGLE_ADS_CLIENT_ID'),
        'client_secret' => env('GOOGLE_ADS_CLIENT_SECRET'),
        'redirect_uri' => env('GOOGLE_ADS_REDIRECT_URI'),
        'developer_token' => env('GOOGLE_ADS_DEVELOPER_TOKEN'),
    ],

    'ai_service' => [
        'url' => env('AI_SERVICE_URL', 'http://ai-service:8001'),
        'api_key' => env('AI_SERVICE_API_KEY'),
    ],

    'metrics' => [
        'sync_interval_minutes' => 15,
        'retention_days' => 365,
    ],

    'automation' => [
        'min_check_interval_minutes' => 15,
        'max_rules_per_org' => 100,
    ],
];
