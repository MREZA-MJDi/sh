<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'پنل اولیا') | آکادمی شیخان</title>
    <script>
        try {
            const theme = localStorage.getItem('guardian-theme');
            const palette = localStorage.getItem('guardian-palette');
            if (theme === 'light' || theme === 'dark') document.documentElement.dataset.theme = theme;
            if (palette) document.documentElement.dataset.pal = palette;
        } catch (error) {}
    </script>
    @vite(['resources/css/parent/app.css', 'resources/css/shared/typography.css', 'resources/js/parent/app.js'])
    @stack('head')
</head>
<body class="parent-page">
    @include('partials.sprite')
    <div class="app parent-app">
        @include('parent.partials.sidebar')
        <main class="main" id="main">
            @include('parent.partials.topbar')
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
    <div class="scrim" data-menu-close></div>
    @stack('scripts')
</body>
</html>
