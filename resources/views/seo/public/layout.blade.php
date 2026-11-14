<!doctype html>
<html lang="fa" dir="rtl">
<head>
    @php
        $canonicalBase = rtrim($settings->canonical_base_url ?: url('/'), '/');
        $seoCanonical = trim((string) $__env->yieldContent('seo-canonical', $canonicalBase.request()->getPathInfo()));
        $seoTitle = trim((string) $__env->yieldContent('seo-title', $settings->default_title));
        $seoDescription = trim((string) $__env->yieldContent('seo-description', $settings->default_description));
        $seoKeywords = trim((string) $__env->yieldContent('seo-keywords', implode(', ', $settings->default_keywords ?? [])));
        $seoImage = trim((string) $__env->yieldContent('seo-image', $settings->default_og_image_path ? Storage::disk('public')->url($settings->default_og_image_path) : ''));
        $seoImageAlt = trim((string) $__env->yieldContent('seo-image-alt', $settings->site_name));
        $organizationData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings->organization_name ?: $settings->site_name,
            'url' => $settings->organization_url ?: $canonicalBase,
            'logo' => $settings->organization_logo_path ? Storage::disk('public')->url($settings->organization_logo_path) : null,
            'sameAs' => array_values(array_filter($settings->social_links ?? [])),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0b1b3a">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    <title>{{ $seoTitle }}{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    @if($seoDescription !== '')<meta name="description" content="{{ $seoDescription }}">@endif
    @if($seoKeywords !== '')<meta name="keywords" content="{{ $seoKeywords }}">@endif
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    @if($settings->google_site_verification)<meta name="google-site-verification" content="{{ $settings->google_site_verification }}">@endif
    @if($settings->bing_site_verification)<meta name="msvalidate.01" content="{{ $settings->bing_site_verification }}">@endif
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ $settings->site_name }}">
    <meta property="og:type" content="@yield('og-type', 'website')">
    <meta property="og:title" content="{{ $seoTitle }}">
    @if($seoDescription !== '')<meta property="og:description" content="{{ $seoDescription }}">@endif
    <meta property="og:url" content="{{ $seoCanonical }}">
    @if($seoImage !== '')
        <meta property="og:image" content="{{ $seoImage }}">
        <meta property="og:image:alt" content="{{ $seoImageAlt }}">
        <meta name="twitter:image" content="{{ $seoImage }}">
        <meta name="twitter:image:alt" content="{{ $seoImageAlt }}">
    @endif
    <meta name="twitter:card" content="{{ $seoImage !== '' ? 'summary_large_image' : 'summary' }}">
    <meta name="twitter:title" content="{{ $seoTitle }}">
    @if($seoDescription !== '')<meta name="twitter:description" content="{{ $seoDescription }}">@endif
    @if($settings->twitter_handle)<meta name="twitter:site" content="{{ '@'.ltrim($settings->twitter_handle, '@') }}">@endif
    <script type="application/ld+json">{!! json_encode($organizationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @vite(['resources/css/app.css', 'resources/css/public-academy.css', 'resources/css/seo/public.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="academy">
    <header class="academy-nav"><div class="ac-wrap ac-nav-row">
        <a class="ac-brand" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>{{ $settings->site_name }}</span></a>
        <button class="ac-menu-toggle" type="button" aria-label="باز کردن منو" aria-expanded="false" data-menu-toggle>☰</button>
        <nav class="ac-nav-links" aria-label="ناوبری اصلی" data-menu>
            <a href="{{ route('home') }}">خانه</a><a href="{{ route('public.courses.index') }}">دوره‌ها</a><a href="{{ route('public.teachers.index') }}">مدرسان</a><a href="{{ route('seo.articles.index') }}">مقالات</a><a href="{{ route('public.contact') }}">تماس با ما</a>
        </nav>
        <div class="ac-nav-actions"><a class="ac-cart-link" href="{{ route('public.cart') }}">سبد خرید</a>
            @auth
                @php($dashboardUrl = auth()->user()->hasRole('student') ? route('student.dashboard') : (auth()->user()->hasRole('parent') ? route('guardian.dashboard') : route('admin.dashboard')))
                <a class="ac-btn ac-btn-small" href="{{ $dashboardUrl }}">پنل من</a>
            @else
                <a class="ac-login-link" href="{{ route('login') }}">ورود</a><a class="ac-btn ac-btn-small" href="{{ route('register') }}">ثبت‌نام</a>
            @endauth
        </div>
    </div></header>
    <main class="seo-container">@yield('content')</main>
    <footer class="ac-footer"><div class="ac-wrap ac-footer-grid"><div><a class="ac-brand ac-brand-light" href="{{ route('home') }}"><span class="ac-brand-mark">ش</span><span>{{ $settings->site_name }}</span></a><p>محتوای آموزشی و مقاله‌های منتشرشده آکادمی شیخان.</p></div><div><h3>دسترسی سریع</h3><a href="{{ route('public.courses.index') }}">دوره‌ها</a><a href="{{ route('public.teachers.index') }}">مدرسان</a><a href="{{ route('seo.articles.index') }}">مقالات</a></div><div><h3>پشتیبانی</h3><a href="{{ route('public.contact') }}">تماس با ما</a>@if($settings->sitemap_enabled)<a href="{{ route('seo.sitemap') }}">نقشه سایت</a>@endif</div></div><div class="ac-wrap ac-copyright">© {{ now()->year }} {{ $settings->site_name }}</div></footer>
    <script>document.querySelector('[data-menu-toggle]')?.addEventListener('click',function(){const m=document.querySelector('[data-menu]');const o=m.classList.toggle('is-open');this.setAttribute('aria-expanded',o?'true':'false')});</script>
</body>
</html>
