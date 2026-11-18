<?php

return [
    // لینک دانلود اپ SpotPlayer که به خریدار نشان داده می‌شود (خالی = دکمه نمایش داده نمی‌شود)
    'spotplayer_app_url' => env('SPOTPLAYER_APP_URL'),

    // license = فقط کلید لایسنس و راهنما | embed = iframe (فقط وقتی SpotPlayer نسخه وب/embed را رسماً تحویل داد)
    'player_mode' => env('LEARNING_PLAYER_MODE', 'license'),
    'embed_url_template' => env('LEARNING_EMBED_URL'), // مثال: https://.../{video_id}

    'max_teaser_mb' => 200,
    'max_lesson_mb' => 250,
    'chunk_mb' => 5,

    // نام route شروع پرداخت پروژه (باید یک Enrollment بگیرد)
    // اسم ستون‌های گزارش «نشست‌ها» در پنل اسکای‌روم (با فایل واقعی خودت تطبیق بده)
    'skyroom_report' => ['name_column' => 'Nickname', 'duration_column' => 'Duration'],

    'payment_route' => env('LEARNING_PAYMENT_ROUTE', 'payment.start'),
];
