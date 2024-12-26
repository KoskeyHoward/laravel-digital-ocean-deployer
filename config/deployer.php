<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    */
    'server' => [
        'host' => env('DO_HOST'),
        'username' => env('DO_USERNAME'),
        'path' => env('DO_PATH', '/var/www/html'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Repository Configuration
    |--------------------------------------------------------------------------
    */
    'repository' => [
        'provider' => 'github',
        'branch' => 'main',
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Steps
    |--------------------------------------------------------------------------
    */
    'steps' => [
        'composer_install' => true,
        'npm_install' => false,
        'npm_build' => false,
        'artisan_migrate' => true,
        'artisan_storage_link' => true,
        'artisan_cache_clear' => true,
        'artisan_config_cache' => true,
        'artisan_route_cache' => true,
        'artisan_view_cache' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Hooks
    |--------------------------------------------------------------------------
    */
    'hooks' => [
        'before' => [
            // Add your custom scripts here
        ],
        'after' => [
            // Add your custom scripts here
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'files' => '644',
        'directories' => '755',
        'storage' => '775',
        'bootstrap_cache' => '775',
    ],
];
