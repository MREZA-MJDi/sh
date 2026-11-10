<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'پنل مدیریت') | آکادمی شیخان</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="panel-body">
<div class="panel-shell">
    @include('admin.partials.sidebar')
    <div class="panel-overlay" data-panel-overlay></div>

    <main class="panel-main">
        <header class="panel-topbar">
            <div class="flex items-center gap-3">
                <button type="button" class="panel-icon-btn panel-mobile-toggle" data-panel-menu-toggle aria-label="باز کردن منو">☰</button>
                <div class="panel-topbar-title">
                    <h1>@yield('page-title', 'داشبورد مدیریت')</h1>
                    <p>@yield('page-description', 'مدیریت یکپارچه آکادمی شیخان')</p>
                </div>
            </div>

            <div class="panel-topbar-actions">
                <div class="panel-global-search">
                    <input type="search" placeholder="جستجو در همین صفحه..." data-table-search aria-label="جستجو در جدول">
                </div>
                <button type="button" class="panel-icon-btn" data-panel-theme-toggle aria-label="تغییر تم" title="تغییر تم">◐</button>
                <div class="panel-user">
                    <div class="panel-user-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</div>
                    <div class="panel-user-meta">
                        <div class="panel-user-name">{{ auth()->user()->name }}</div>
                        <div class="panel-user-role">مدیر سامانه</div>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.logout') }}">
                    @csrf
                    <button class="panel-icon-btn" type="submit" title="خروج از حساب" aria-label="خروج">↪</button>
                </form>
            </div>
        </header>

        <div class="panel-content">
            @include('admin.partials.flash')
            @yield('content')
        </div>
    </main>
</div>
</body>
</html>
