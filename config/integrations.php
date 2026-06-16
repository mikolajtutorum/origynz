<?php

return [

    'familysearch' => [
        'client_id'     => env('FAMILYSEARCH_CLIENT_ID'),
        'client_secret' => env('FAMILYSEARCH_CLIENT_SECRET'),
        'redirect'      => env('FAMILYSEARCH_REDIRECT_URI', '/integrations/familysearch/callback'),
        'base_url'      => env('FAMILYSEARCH_BASE_URL', 'https://api.familysearch.org'),
        'auth_url'      => 'https://ident.familysearch.org/cis-web/oauth2/v3/authorization',
        'token_url'     => 'https://ident.familysearch.org/cis-web/oauth2/v3/token',
        'sandbox'       => env('FAMILYSEARCH_SANDBOX', false),
    ],

    'wikitree' => [
        'base_url' => 'https://api.wikitree.com/api.php',
    ],

    'geni' => [
        'client_id'     => env('GENI_CLIENT_ID'),
        'client_secret' => env('GENI_CLIENT_SECRET'),
        'redirect'      => env('GENI_REDIRECT_URI', '/integrations/geni/callback'),
        'base_url'      => 'https://www.geni.com',
        'api_url'       => 'https://www.geni.com/api',
        'auth_url'      => 'https://www.geni.com/platform/oauth/authorize',
        'token_url'     => 'https://www.geni.com/platform/oauth/request_token',
    ],

    'dna' => [
        'disk'         => env('DNA_STORAGE_DISK', 'local'),
        'max_size_mb'  => (int) env('DNA_MAX_SIZE_MB', 150),
        'path_prefix'  => 'dna-kits',
    ],

    /*
     * Public REST API settings
     */
    'api' => [
        'rate_limit'    => (int) env('API_RATE_LIMIT', 60),   // requests per minute
        'token_expiry'  => (int) env('API_TOKEN_EXPIRY', 0),  // 0 = never
    ],

];
