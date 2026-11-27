<?php

return [
    'document_reader_bin' => env('DOCUMENT_READER_BIN'),
    // لینک دانلود اپ SpotPlayer که به خریدار نشان داده می‌شود (خالی = دکمه نمایش داده نمی‌شود)
    'spotplayer_app_url' => env('SPOTPLAYER_APP_URL'),

    // license = فقط کلید لایسنس و راهنما | embed = iframe (فقط وقتی SpotPlayer نسخه وب/embed را رسماً تحویل داد)
    'player_mode' => env('LEARNING_PLAYER_MODE', 'license'),
    'embed_url_template' => env('LEARNING_EMBED_URL'), // مثال: https://.../{video_id}

    // Keep CPU-intensive video work bounded on shared VPS hosts.
    'video_threads' => max(1, min(8, (int) env('LEARNING_VIDEO_THREADS', 1))),
    // Enable only after the internal Nginx location has been installed and tested.
    'nginx_private_video' => env('LEARNING_NGINX_PRIVATE_VIDEO', false),

    'max_teaser_mb' => 200,
    'max_lesson_mb' => 300,
    'chunk_mb' => max(1, (int) env('LEARNING_CHUNK_MB', 1)),
    'upload_session_ttl_hours' => max(1, (int) env('LEARNING_UPLOAD_SESSION_TTL_HOURS', 24)),

    // نام route شروع پرداخت پروژه (باید یک Enrollment بگیرد)
    // اسم ستون‌های گزارش «نشست‌ها» در پنل اسکای‌روم (با فایل واقعی خودت تطبیق بده)
    'skyroom_report' => ['name_column' => 'Nickname', 'duration_column' => 'Duration'],

    'payment_route' => env('LEARNING_PAYMENT_ROUTE', 'payment.start'),
];
