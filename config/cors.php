<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| In production, set CORS_ALLOWED_ORIGINS in .env to a comma-separated list
| of trusted frontend origins, e.g.:
|
|   CORS_ALLOWED_ORIGINS="https://melazmotors.com,https://www.melazmotors.com"
|
| If CORS_ALLOWED_ORIGINS is empty / unset, all origins are allowed (handy
| in local dev; do NOT leave it unset in production).
|
*/

$allowedOrigins = array_values(array_filter(array_map(
    'trim',
    explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))
)));

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'HEAD', 'OPTIONS'],

    'allowed_origins' => $allowedOrigins !== [] ? $allowedOrigins : ['*'],

    'allowed_origins_patterns' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', ''))
    ))),

    'allowed_headers' => ['Accept', 'Content-Type', 'Authorization', 'X-Requested-With', 'Origin'],

    'exposed_headers' => [],

    'max_age' => (int) env('CORS_MAX_AGE', 86400),

    'supports_credentials' => false,

];
