<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('page-title', 'داشبورد') | آکادمی شیخان</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    <script>
        try {
            const savedAdminTheme = localStorage.getItem('shikhan-admin-theme');
            if (savedAdminTheme === 'dark' || savedAdminTheme === 'light') document.documentElement.dataset.adminTheme = savedAdminTheme;
        } catch (error) { /* Theme persistence is optional. */ }
    </script>
    @vite(['resources/css/admin/app.css', 'resources/js/admin/app.js'])
    @stack('head')
</head>
<body class="admin-body" data-theme="light">
    @php($adminUser = auth()->user())
    @include('admin.partials.sprite')
    <div class="admin-shell">
        @include('admin.partials.sidebar')
        <div class="admin-scrim" data-admin-close></div>
        <main class="panel-main admin-main" id="admin-main">
            @include('admin.partials.topbar')
            <section class="admin-content">
                @include('admin.partials.flash')
                @yield('content')
            </section>
        </main>
    </div>
    <aside class="admin-drawer" id="admin-drawer" aria-hidden="true" aria-label="فرم">
        <div class="admin-drawer-head"><h2 data-drawer-title>فرم</h2><button type="button" class="admin-icon-btn" data-admin-close aria-label="بستن">×</button></div>
        <div class="admin-drawer-body" data-drawer-body></div>
    </aside>
    <div class="admin-toasts" id="admin-toasts" role="status" aria-live="polite"></div>
    @stack('scripts')
</body>
</html>
