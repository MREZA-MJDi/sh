<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>@yield('title', 'پنل دانش‌آموز') · شیخان</title>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">
@vite(['resources/css/student/app.css', 'resources/js/student/student.js'])
<script>try{var t=localStorage.getItem('sh-theme');if(t)document.documentElement.setAttribute('data-theme',t)}catch(e){}</script>
@stack('head')
</head>
<body>
@include('student.partials.icons')

<div class="app">
    @include('student.partials.sidebar')

    <main class="main">
        @include('student.partials.topbar')
        @yield('content')
    </main>
</div>

<div id="toast" class="toast" role="status" aria-live="polite"></div>


@stack('scripts')
</body>
</html>
