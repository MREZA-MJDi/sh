<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local visual-review accounts
    |--------------------------------------------------------------------------
    |
    | Demo users are never created unless explicitly enabled and the current
    | application environment is local or testing.
    |
    */
    'enabled' => (bool) env('SHIKHAN_DEMO_ACCOUNTS', false),
    'password' => env('SHIKHAN_DEMO_PASSWORD'),
];
