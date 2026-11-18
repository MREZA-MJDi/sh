<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>@yield('title', 'پنل مدرس') · پنل مدرس</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
@vite(['resources/css/teacher/teacher.css', 'resources/js/teacher/teacher.js'])
<script>try{var d=document.documentElement,t=localStorage.getItem('th-theme'),p=localStorage.getItem('th-pal');if(t)d.dataset.theme=t;if(p)d.dataset.pal=p}catch(e){}</script>
@stack('head')
</head>
<body>
@include('teacher.partials.topbar')

<div class="app">
    @include('teacher.partials.sidebar')
    <main data-fa>
        @if (session('success'))
            <div class="teacher-alert success" role="status">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="teacher-alert error" role="alert">{{ session('error') }}</div>
        @endif
        @if ($errors->any())
            <div class="teacher-alert error" role="alert">
                <strong>ذخیره انجام نشد؛ لطفاً موارد زیر را بررسی کنید.</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>

@include('teacher.partials.modals')

<div id="t" role="status"></div>

@stack('scripts')
</body>
</html>
