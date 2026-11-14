<!doctype html>
<html lang="fa" dir="rtl">
<head>
    @php
        $settings = $settings ?? \App\Models\SeoSetting::current();
        $canonicalBase = rtrim($settings->canonical_base_url ?: url('/'), '/');
        $seoCanonical = trim((string) $__env->yieldContent('canonical', $canonicalBase . (request()->getPathInfo() === '/' ? '' : request()->getPathInfo())));
        $seoTitle = trim((string) $__env->yieldContent('title', ($settings->default_title ?? 'شیخان') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'پلتفرم آموزش آنلاین')));
        $seoDescription = trim((string) $__env->yieldContent('description', $settings->default_description ?? ''));
        $seoRobots = trim((string) $__env->yieldContent('robots', (($settings->robots_index ?? true) ? 'index' : 'noindex') . ',' . (($settings->robots_follow ?? true) ? 'follow' : 'nofollow')));
        $defaultOg = !empty($settings->default_og_image_path) ? Storage::disk('public')->url($settings->default_og_image_path) : '';
        $seoImage = trim((string) $__env->yieldContent('seo-image', $defaultOg));
        $seoImageAlt = trim((string) $__env->yieldContent('seo-image-alt', $settings->site_name ?? 'شیخان'));

        $organizationData = array_filter([
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $settings->organization_name ?: ($settings->site_name ?? 'شیخان'),
            'url' => $settings->organization_url ?: $canonicalBase,
            'logo' => !empty($settings->organization_logo_path) ? Storage::disk('public')->url($settings->organization_logo_path) : null,
            'sameAs' => array_values(array_filter($settings->social_links ?? [])),
        ], fn ($value) => $value !== null && $value !== '');
    @endphp
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1b3a">
    <title>{{ $seoTitle }}</title>
    @if($seoDescription !== '')<meta name="description" content="{{ $seoDescription }}">@endif
    <meta name="robots" content="{{ $seoRobots }}">
    @if(!empty($settings->google_site_verification))<meta name="google-site-verification" content="{{ $settings->google_site_verification }}">@endif
    @if(!empty($settings->bing_site_verification))<meta name="msvalidate.01" content="{{ $settings->bing_site_verification }}">@endif
    <link rel="canonical" href="{{ $seoCanonical }}">
    <meta property="og:locale" content="fa_IR">
    <meta property="og:site_name" content="{{ $settings->site_name ?? 'شیخان' }}">
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
    @if(!empty($settings->twitter_handle))<meta name="twitter:site" content="{{ '@'.ltrim($settings->twitter_handle, '@') }}">@endif
    @if(!empty($organizationData))
        <script type="application/ld+json">{!! json_encode($organizationData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/css/public-academy.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="academy">
@include('public.partials.sprite')

@include('public.partials.header')

@if(session('status'))
    <div class="c" style="margin-top:16px"><div class="ac-alert" role="status">{{ session('status') }}</div></div>
@endif

<main>
    @yield('content')
</main>

<footer>
    <div class="c">
        <div class="g-footer">
            <div>
                <a class="logo" href="{{ route('home') }}" style="color:#fff;margin-bottom:12px">
                    <svg class="i"><use href="#cap"/></svg>
                    <span>{{ $settings->site_name ?? 'شیخان' }}</span>
                </a>
                <p>آموزشگاه شیخان؛ آموزش اصولی و ساختارمند همراه با نظارت و پیگیری مستمر اساتید تا نتیجه مطلوب.</p>
            </div>
            <div>
                <h4>دوره‌ها</h4>
                <a href="{{ route('public.courses.index', ['subject' => 'ریاضی']) }}">ریاضیات</a>
                <a href="{{ route('public.courses.index', ['subject' => 'فیزیک']) }}">فیزیک</a>
                <a href="{{ route('public.courses.index', ['subject' => 'شیمی']) }}">شیمی</a>
                <a href="{{ route('public.courses.index', ['subject' => 'زیست']) }}">زیست‌شناسی</a>
                <a href="{{ route('public.courses.index') }}">همه دوره‌ها</a>
            </div>
            <div>
                <h4>دسترسی سریع</h4>
                <a href="{{ route('home') }}">صفحه اصلی</a>
                <a href="{{ route('public.courses.index') }}">آرشیو دوره‌ها</a>
                <a href="{{ route('public.teachers.index') }}">مدرسان شیخان</a>
                <a href="{{ route('seo.articles.index') }}">مجله مقالات</a>
            </div>
            <div>
                <h4>پنل‌های کاربری</h4>
                <a href="{{ route('student.dashboard') }}">پنل دانش‌آموز</a>
                <a href="{{ route('teacher.dashboard') }}">پنل مدرس</a>
                <a href="{{ route('guardian.dashboard') }}">پنل والدین</a>
                <a href="{{ route('admin.dashboard') }}">پنل مدیریت</a>
            </div>
            <div>
                <h4>پشتیبانی و ارتباط</h4>
                <a href="{{ route('public.contact') }}">مشاوره و تماس</a>
                <a href="{{ route('login') }}">ورود به حساب</a>
                <a href="{{ route('register') }}">ثبت‌نام جدید</a>
                <a href="{{ route('public.cart') }}">سبد خرید</a>
            </div>
        </div>
        <div class="cp">© {{ now()->year }} {{ $settings->site_name ?? 'شیخان' }} · تمامی حقوق محفوظ است.</div>
    </div>
</footer>

@stack('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const burgerBtn = document.getElementById('bg');
    const linksNav = document.getElementById('lk');
    const searchBtn = document.getElementById('sb');
    const searchBar = document.getElementById('sbar');
    const searchInput = document.getElementById('hq');
    const panelBtn = document.getElementById('pb');
    const panelMenu = document.getElementById('pm');

    if (burgerBtn && linksNav) {
        burgerBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            linksNav.classList.toggle('open');
        });
    }

    if (searchBtn && searchBar) {
        searchBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            searchBar.classList.toggle('on');
            if (searchBar.classList.contains('on') && searchInput) {
                searchInput.focus();
            }
        });
    }

    if (panelBtn && panelMenu) {
        panelBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            panelMenu.classList.toggle('on');
        });
    }

    document.addEventListener('click', function (e) {
        if (panelMenu && !panelMenu.contains(e.target) && e.target !== panelBtn) {
            panelMenu.classList.remove('on');
        }
        if (linksNav && !linksNav.contains(e.target) && e.target !== burgerBtn) {
            linksNav.classList.remove('open');
        }
    });
});
</script>
</body>
</html>
