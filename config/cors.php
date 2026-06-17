<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | SECURITY: Restrict to specific known domains.
    | Wildcard origins allow any website to make API requests.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],

    // SECURITY FIX: Restrict to specific production domains
    // Update these with your actual production domains
    'allowed_origins' => [
        env('APP_URL', 'http://localhost'),
        // Add your company domains here:
        // 'https://kwatogs.example.com',
        // 'https://hris.kwatogs.com',
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'X-CSRF-TOKEN', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 86400, // Cache preflight for 24 hours

    'supports_credentials' => true, // Required for Sanctum SPA auth

];