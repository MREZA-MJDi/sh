<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Temporary client-demo accounts
    |--------------------------------------------------------------------------
    |
    | Demo users are never created unless explicitly enabled. Login cards
    | require a second explicit flag and may be enabled for a short production
    | demo window. The SEO role is intentionally excluded from public cards.
    |
    */
    'enabled' => (bool) env('SHIKHAN_DEMO_ACCOUNTS', false),
    'login_cards_enabled' => (bool) env('SHIKHAN_DEMO_LOGIN_CARDS', false),
    'password' => env('SHIKHAN_DEMO_PASSWORD'),
    'seed_academic_content' => (bool) env('SHIKHAN_SEED_ACADEMY_CONTENT', false),
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
