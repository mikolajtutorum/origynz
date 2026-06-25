<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | The React SPA (origynz.ddev.site) and the API (origynzapi.ddev.site) are
    | different origins, so the API must send CORS headers. Auth is via bearer
    | tokens (Authorization header), not cookies, so credentials are NOT shared.
    |
    */

    'paths' => ['api/*', 'auth/*', 'up'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'https://origynz.ddev.site'),
    ]),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 60 * 60,

    'supports_credentials' => false,

];
