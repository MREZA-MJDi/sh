<?php

return [
    // Demo credentials are used only in local and testing environments.
    'enabled' => (bool) env('SHIKHAN_DEMO_ACCOUNTS', false),
    'login_cards_enabled' => (bool) env('SHIKHAN_DEMO_LOGIN_CARDS', false),
    'password' => env('SHIKHAN_DEMO_PASSWORD'),
    'accounts' => [
        [
            'name' => 'مدیر آزمایشی',
            'label' => 'پنل مدیریت',
            'email' => 'admin@sh.local',
            'role' => 'admin',
        ],
        [
            'name' => 'کارشناس SEO آزمایشی',
            'label' => 'پنل SEO',
            'email' => 'seo@sh.local',
            'role' => 'seo',
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
