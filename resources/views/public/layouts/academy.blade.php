<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b1b3a">
    <title>@yield('title', ($settings->default_title ?? 'شیخان') . ($settings->title_separator ?? ' | ') . ($settings->site_name ?? 'پلتفرم آموزش آنلاین'))</title>
    <meta name="description" content="@yield('description', $settings->default_description ?? '')">
    <meta name="robots" content="@yield('robots', (($settings->robots_index ?? true) ? 'index' : 'noindex').','.($settings->robots_follow ?? true ? 'follow' : 'nofollow'))">
    <link rel="canonical" href="@yield('canonical', url()->current())">
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
