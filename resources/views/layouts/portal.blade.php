<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'کارتابل') | آکادمی شیخان</title>
    <script>
        try {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light' || savedTheme === 'dark') {
                document.documentElement.dataset.theme = savedTheme;
            }
        } catch (error) {
            // Theme persistence is optional when browser storage is unavailable.
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">
    @vite(['resources/css/student/app.css', 'resources/js/student/app.js'])
    @stack('head')
</head>
<body class="student-page">
    @php $portalRole = request()->routeIs('guardian.*') ? 'parent' : 'student'; @endphp
    @include('partials.sprite')

    <div class="app">
        @include('portal.partials.sidebar')
        <div class="scrim" data-menu-close></div>

        <main class="main" id="main">
            @include('portal.partials.topbar')
            @include('partials.flash')
            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
