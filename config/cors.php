<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'logout', '*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    /*
     * Allow multiple origins for production deployment
     * Include both your frontend domain and backend domain
     */
    'allowed_origins' => [
        'https://turamunicipalboard.com',
        'https://www.turamunicipalboard.com',
        'https://laravelv2.turamunicipalboard.com',
        'http://localhost:3000',
        'http://127.0.0.1:3000',
        env('FRONTEND_URL', env('APP_URL'))
    ],

    'allowed_origins_patterns' => [
        '#^https://.*\.turamunicipalboard\.com$#',
        '#^http://localhost:\d+$#',
        '#^http://127\.0\.0\.1:\d+$#'
    ],

    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'X-CSRF-TOKEN',
        'X-Socket-ID',
        'Origin',
        'Cache-Control',
        'Pragma'
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma'
    ],

    'max_age' => 86400, // 24 hours

    'supports_credentials' => true,

];
