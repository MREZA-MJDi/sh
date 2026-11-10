<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
    <title>@yield('title', 'کارتابل') | آکادمی شیخان</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">
    @vite(['resources/css/student/app.css', 'resources/js/student/app.js'])
    @stack('head')
</head>
@php($portalRole = request()->routeIs('guardian.dashboard') ? 'parent' : 'student')
<body class="reference-dashboard-page {{ $portalRole === 'parent' ? 'reference-parent' : 'reference-student' }}">
    @include('partials.sprite')
    <div class="app">
        @include('portal-reference.partials.sidebar')
        <div class="scrim" data-menu-close></div>
        <main class="main" id="main">
            @include('portal-reference.partials.topbar')
            @include('partials.flash')
            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
