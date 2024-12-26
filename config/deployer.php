<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    |
    | These values are automatically pulled from GitHub secrets during deployment.
    | You don't need to set them in your .env file.
    |
    */
    'server' => [
        'host' => null,      // Uses DO_HOST from GitHub secrets
        'username' => null,  // Uses DO_USERNAME from GitHub secrets
        'ssh_key' => null,  // Uses DO_SSH_KEY from GitHub secrets
        'path' => null,     // Uses DO_PATH from GitHub secrets
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
    |
    | Configure which deployment steps should be executed
    |
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
    |
    | Define custom commands to run before or after deployment
    |
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
