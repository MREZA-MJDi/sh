<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1b3a">
    <title>@yield('title', $settings->default_title . $settings->title_separator . $settings->site_name)</title>
    <meta name="description" content="@yield('description', $settings->default_description)">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="@yield('canonical', url()->current())">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/css/public-academy.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="academy">
<header class="academy-nav">
    <div class="ac-wrap ac-nav-row">
        <a class="ac-brand" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>{{ $settings->site_name }}</span></a>
        <button class="ac-menu-toggle" type="button" aria-label="باز کردن منو" aria-expanded="false" data-menu-toggle>☰</button>
        <nav class="ac-nav-links" aria-label="ناوبری اصلی" data-menu>
            <a href="{{ route('home') }}">خانه</a>
            <a href="{{ route('public.courses.index') }}">دوره‌ها</a>
            <a href="{{ route('public.teachers.index') }}">مدرسان</a>
            <a href="{{ route('seo.articles.index') }}">مقالات</a>
            <a href="{{ route('public.contact') }}">تماس با ما</a>
        </nav>
        <div class="ac-nav-actions">
            <a class="ac-cart-link" href="{{ route('public.cart') }}">سبد خرید <span class="ac-cart-count">{{ count(session('public_cart', [])) }}</span></a>
            @auth
                @php($dashboardUrl = auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('parent') ? route('guardian.dashboard') : route('admin.dashboard')))
                <a class="ac-btn ac-btn-small" href="{{ $dashboardUrl }}">پنل من</a>
            @else
                <a class="ac-login-link" href="{{ route('login') }}">ورود</a>
                <a class="ac-btn ac-btn-small" href="{{ route('register') }}">ثبت‌نام</a>
            @endauth
        </div>
    </div>
</header>
@if(session('status'))<div class="ac-wrap"><div class="ac-alert" role="status">{{ session('status') }}</div></div>@endif
<main>@yield('content')</main>
<footer class="ac-footer">
    <div class="ac-wrap ac-footer-grid">
        <div><a class="ac-brand ac-brand-light" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>{{ $settings->site_name }}</span></a><p>آموزش اصولی، مسیر روشن و دسترسی به محتوای آموزشی منتشرشده.</p></div>
        <div><h3>دسترسی سریع</h3><a href="{{ route('public.courses.index') }}">دوره‌ها</a><a href="{{ route('public.teachers.index') }}">مدرسان</a><a href="{{ route('seo.articles.index') }}">مقالات</a></div>
        <div><h3>پشتیبانی</h3><a href="{{ route('public.contact') }}">تماس با ما</a><a href="{{ route('login') }}">ورود</a><a href="{{ route('register') }}">ثبت‌نام</a></div>
    </div>
    <div class="ac-wrap ac-copyright">© {{ now()->year }} {{ $settings->site_name }} · تمامی حقوق محفوظ است.</div>
</footer>
@stack('scripts')
<script>
document.querySelector('[data-menu-toggle]')?.addEventListener('click', function () {
    const menu = document.querySelector('[data-menu]');
    const open = menu.classList.toggle('is-open');
    this.setAttribute('aria-expanded', open ? 'true' : 'false');
});
</script>
</body>
</html>
