<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Server Configuration
    |--------------------------------------------------------------------------
    |
    | These settings are used to connect to your DigitalOcean server.
    | For local development, set these in your .env file:
    |   DO_HOST=your-server-ip
    |   DO_USERNAME=your-server-username
    |   DO_SSH_KEY=your-base64-encoded-private-key
    |   DO_PATH=/path/to/your/app
    |
    | For GitHub Actions deployment, set these as repository secrets:
    |   DO_HOST
    |   DO_USERNAME
    |   DO_SSH_KEY
    |   DO_PATH
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
        'branch' => env('DEPLOY_BRANCH', 'main'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Steps
    |--------------------------------------------------------------------------
    |
    | Configure which deployment steps should be executed.
    |
    */
    'steps' => [
        'composer_install' => true,
        'npm_install' => true,
        'npm_build' => true,
        'artisan_migrate' => true,
        'artisan_storage_link' => true,
        'artisan_cache_clear' => true,
        'artisan_config_cache' => true,
        'artisan_route_cache' => true,
        'artisan_view_cache' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Hooks
    |--------------------------------------------------------------------------
    |
    | You may add terminal commands that should be run before or after deployment.
    |
    */
    'hooks' => [
        'before' => [
            // 'command to run before deployment',
        ],
        'after' => [
            // 'command to run after deployment',
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
