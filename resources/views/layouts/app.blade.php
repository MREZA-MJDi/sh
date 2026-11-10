<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow, noarchive">
<title>@yield('title', 'کارتابل') · {{ $m['academy']['name'] }}</title>
<script>try{var t=localStorage.getItem('theme');if(t)document.documentElement.dataset.theme=t}catch(e){}</script>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@300;400;500;600;700;800&display=swap">
@vite(['resources/css/app.css', 'resources/js/app.js'])
@stack('head')
</head>
<body>
@include('partials.sprite')
<div class="app">
  @include('partials.sidebar')
  <div class="scrim" data-menu-close></div>
  <main class="main" id="main">
    @include('partials.topbar')
    @include('partials.flash')
    @yield('content')
  </main>
</div>
@stack('scripts')
</body>
</html>
