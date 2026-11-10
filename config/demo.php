<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Local visual-review accounts
    |--------------------------------------------------------------------------
    |
    | Demo users are never created unless explicitly enabled and the current
    | application environment is local or testing. Credentials are only sent
    | to the login view in the local environment.
    |
    */
    'enabled' => (bool) env('SHIKHAN_DEMO_ACCOUNTS', false),
    'password' => env('SHIKHAN_DEMO_PASSWORD'),
    'accounts' => [
        [
            'name' => 'مدیر آزمایشی',
            'label' => 'پنل مدیریت',
            'email' => 'admin@sh.local',
            'role' => 'admin',
        ],
        [
            'name' => 'مدرس آزمایشی',
            'label' => 'پنل مدرس',
            'email' => 'teacher@sh.local',
            'role' => 'teacher',
        ],
        [
            'name' => 'دانش‌آموز آزمایشی',
            'label' => 'پنل دانش‌آموز',
            'email' => 'student@sh.local',
            'role' => 'student',
        ],
        [
            'name' => 'ولی آزمایشی',
            'label' => 'پنل والدین',
            'email' => 'parent@sh.local',
            'role' => 'parent',
        ],
    ],
];
