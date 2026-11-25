<?php
/* Sidebar structure. `url` is matched with request()->is(url, url/*). Colours come from data-tone in 01-tokens.css.
   Groups mirror the client's role diagram: users · education · tracking · communication · shop. */
return [
    'home' => ['label' => 'داشبورد', 'url' => 'admin', 'icon' => 'home'],
    'groups' => [
        ['label' => 'کاربران', 'tone' => 'users', 'items' => [
            ['label' => 'دانش‌آموزان', 'url' => 'admin/students', 'icon' => 'user'],
            ['label' => 'مدرسان', 'url' => 'admin/teachers', 'icon' => 'teacher'],
            ['label' => 'اولیا', 'url' => 'admin/parents', 'icon' => 'family'],
        ]],
        ['label' => 'آموزش', 'tone' => 'edu', 'items' => [
            ['label' => 'مرکز مدیریت آموزش', 'url' => 'admin/lms', 'icon' => 'book'],
            ['label' => 'دوره‌ها', 'url' => 'admin/courses', 'icon' => 'book'],
            ['label' => 'گروه‌های آموزشی', 'url' => 'admin/classes', 'icon' => 'cls'],
            ['label' => 'جلسات آنلاین', 'url' => 'admin/online-classes', 'icon' => 'live'],
            ['label' => 'ویدیوهای ضبط‌شده', 'url' => 'admin/spotplayer', 'icon' => 'video'],
        ]],
        ['label' => 'پیگیری و ارزیابی', 'tone' => 'track', 'items' => [
            ['label' => 'حضور و غیاب', 'url' => 'admin/attendance', 'icon' => 'check'],
            ['label' => 'تکالیف', 'url' => 'admin/homework', 'icon' => 'file'],
            ['label' => 'آزمون‌ها', 'url' => 'admin/exams', 'icon' => 'exam'],
            ['label' => 'یکپارچه‌سازی Quiz24', 'url' => 'admin/quiz24', 'icon' => 'exam'],
        ]],
        ['label' => 'ارتباطات', 'tone' => 'comm', 'items' => [
            ['label' => 'مرکز ارتباطات', 'url' => 'admin/communication-center', 'icon' => 'chat'],
            ['label' => 'کارتابل پیام‌ها', 'url' => 'admin/messages', 'icon' => 'msg'],
            ['label' => 'اطلاعیه‌های عمومی', 'url' => 'admin/announcements', 'icon' => 'bell'],
            ['label' => 'اعلان‌های درون‌پنلی', 'url' => 'admin/internal-notifications', 'icon' => 'bell'],
        ]],
        ['label' => 'ثبت‌نام و فروش', 'tone' => 'shop', 'items' => [
            ['label' => 'ثبت‌نام‌ها و مجوزها', 'url' => 'admin/enrollments', 'icon' => 'lock'],
            ['label' => 'پرداخت‌ها', 'url' => 'admin/payments', 'icon' => 'card'],
            ['label' => 'منابع و محصولات', 'url' => 'admin/resources', 'icon' => 'lib'],
        ]],
        ['label' => 'محتوا و بهینه‌سازی', 'tone' => 'shop', 'items' => [
            ['label' => 'مرکز مدیریت سئو', 'url' => 'admin/seo', 'icon' => 'search'],
            ['label' => 'مقاله‌ها', 'url' => 'admin/seo/articles', 'icon' => 'file'],
            ['label' => 'اسلایدر و بنرها', 'url' => 'admin/seo/banners', 'icon' => 'image'],
            ['label' => 'نظرات دانش‌آموزان', 'url' => 'admin/seo/testimonials', 'icon' => 'check'],
        ]],
    ],
];
