<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="robots" content="noindex, nofollow, noarchive">
<title>@yield('title', 'پنل اولیا') · پنل اولیا</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
@vite(['resources/css/parent/parent.css', 'resources/js/parent/parent.js'])
<script>try{var d=document.documentElement,t=localStorage.getItem('pa-theme'),p=localStorage.getItem('pa-pal');if(t)d.dataset.theme=t;if(p)d.dataset.pal=p}catch(e){}</script>
@stack('head')
</head>
<body>
<a class="parent-skip-link" href="#parentMain">رفتن به محتوای اصلی</a>
@include('parent.partials.topbar')
<button type="button" class="parent-nav-backdrop" id="parentNavBackdrop" aria-label="بستن منوی اصلی" hidden></button>

<div class="app">
    @include('parent.partials.sidebar')
    <main id="parentMain" tabindex="-1" data-fa>
        @if (session('success') || session('status'))<div class="parent-alert success" role="status">{{ session('success') ?? session('status') }}</div>@endif
        @if (session('error'))<div class="parent-alert error" role="alert">{{ session('error') }}</div>@endif
        @if ($errors->any())<div class="parent-alert error" role="alert"><strong>ذخیره انجام نشد؛ موارد زیر را بررسی کنید.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        @yield('content')
    </main>
</div>

<div id="t" role="status"></div>

@stack('scripts')
</body>
</html>
