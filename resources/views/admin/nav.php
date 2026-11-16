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
            ['label' => 'دوره‌ها', 'url' => 'admin/courses', 'icon' => 'book'],
            ['label' => 'کلاس‌ها', 'url' => 'admin/classes', 'icon' => 'cls'],
            ['label' => 'کلاس‌های آنلاین', 'url' => 'admin/online-classes', 'icon' => 'live'],
            ['label' => 'ویدیوهای ضبط‌شده', 'url' => 'admin/recordings', 'icon' => 'video'],
        ]],
        ['label' => 'پیگیری و ارزیابی', 'tone' => 'track', 'items' => [
            ['label' => 'حضور و غیاب', 'url' => 'admin/attendance', 'icon' => 'check'],
            ['label' => 'تکالیف', 'url' => 'admin/assignments', 'icon' => 'file'],
            ['label' => 'آزمون‌ها', 'url' => 'admin/exams', 'icon' => 'exam'],
        ]],
        ['label' => 'ارتباطات', 'tone' => 'comm', 'items' => [
            ['label' => 'پیام‌های اولیا', 'url' => 'admin/parent-messages', 'icon' => 'msg', 'badge' => 5],
            ['label' => 'جلسات با اولیا', 'url' => 'admin/meetings', 'icon' => 'cal'],
            ['label' => 'مشاوره‌ها', 'url' => 'admin/consultations', 'icon' => 'chat'],
            ['label' => 'اعلان‌ها', 'url' => 'admin/announcements', 'icon' => 'bell'],
        ]],
        ['label' => 'ثبت‌نام و فروش', 'tone' => 'shop', 'items' => [
            ['label' => 'ثبت‌نام‌ها و مجوزها', 'url' => 'admin/enrollments', 'icon' => 'lock'],
            ['label' => 'پرداخت‌ها', 'url' => 'admin/payments', 'icon' => 'card', 'badge' => 7],
            ['label' => 'منابع و محصولات', 'url' => 'admin/resources', 'icon' => 'lib'],
        ]],
    ],
];
