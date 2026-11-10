<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local visual-review accounts
    |--------------------------------------------------------------------------
    |
    | Enabled by default only for local development. The seeder independently
    | blocks every environment except local/testing, and the login page only
    | displays credentials in local development.
    |
    */
    'enabled' => (bool) env('SHIKHAN_DEMO_ACCOUNTS', env('APP_ENV') === 'local'),
    'password' => env(
        'SHIKHAN_DEMO_PASSWORD',
        env('APP_ENV') === 'local' ? 'ShikhanDemo!2026' : null
    ),
];
