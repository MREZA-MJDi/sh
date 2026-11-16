<!doctype html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>@yield('title', 'پنل اولیا') · پنل اولیا</title>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;800&display=swap" rel="stylesheet">
@vite(['resources/css/parent/parent.css', 'resources/js/parent/parent.js'])
<script>try{var d=document.documentElement,t=localStorage.getItem('pa-theme'),p=localStorage.getItem('pa-pal');if(t)d.dataset.theme=t;if(p)d.dataset.pal=p}catch(e){}</script>
@stack('head')
</head>
<body>
@include('parent.partials.topbar')

<div class="app">
    @include('parent.partials.sidebar')
    <main data-fa>
        @yield('content')
    </main>
</div>

<div id="t" role="status"></div>

@stack('scripts')
</body>
</html>
