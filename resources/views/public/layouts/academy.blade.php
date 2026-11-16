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
    @endif\n    @vite(['resources/css/app.css', 'resources/css/public-academy.css', 'resources/css/shared/typography.css', 'resources/js/app.js'])
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
                    @if($settings->organization_logo_path)
                        <img src="{{ Storage::disk('public')->url($settings->organization_logo_path) }}" alt="{{ $settings->organization_name ?: $settings->site_name }}" style="max-width:150px;max-height:56px;object-fit:contain">
                    @else
                        <svg class="i"><use href="#cap"/></svg><span>{{ $settings->site_name }}</span>
                    @endif
                </a>
                <p>{{ $settings->footer_description ?: $settings->default_description }}</p>
                @if($settings->trust_badge_path)
                    <img src="{{ Storage::disk('public')->url($settings->trust_badge_path) }}" alt="نماد اعتماد" loading="lazy" style="display:block;max-width:120px;max-height:76px;object-fit:contain;margin-top:18px">
                @endif
            </div>
            <div>
                <h4>دسترسی سریع</h4>
                @forelse(($settings->footer_links ?? []) as $footerLink)
                    @php($footerPath = (string) ($footerLink['url'] ?? ''))
                    @php($footerScheme = strtolower((string) parse_url($footerPath, PHP_URL_SCHEME)))
                    @php($footerUrl = str_starts_with($footerPath, '/') && ! str_starts_with($footerPath, '//') ? url($footerPath) : (in_array($footerScheme, ['http', 'https'], true) ? $footerPath : '#'))
                    <a href="{{ $footerUrl }}">{{ $footerLink['label'] ?? '' }}</a>
                @empty
                    <span>لینکی برای نمایش ثبت نشده است.</span>
                @endforelse
            </div>
            <div>
                <h4>حساب کاربری</h4>
                <a href="{{ route('login') }}">ورود</a>
                <a href="{{ route('register') }}">ساخت حساب</a>
            </div>
            <div>
                <h4>شبکه‌های اجتماعی</h4>
                <div class="seo-social-icons" aria-label="شبکه‌های اجتماعی آکادمی">
                    @if(!empty($settings->social_links['instagram']))
                        <a href="{{ $settings->social_links['instagram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Instagram" style="color:#E4405F"><svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="2"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor"/></svg></a>
                    @endif
                    @if(!empty($settings->social_links['telegram']))
                        <a href="{{ $settings->social_links['telegram'] }}" target="_blank" rel="noopener noreferrer" aria-label="Telegram" style="color:#229ED9"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M21.7 4.3 18.5 20c-.2 1.1-.9 1.3-1.8.8l-5-3.7-2.4 2.3c-.3.3-.5.5-1 .5l.4-5.1 9.3-8.4c.4-.4-.1-.6-.6-.2L6 13.5l-4.9-1.6c-1.1-.3-1.1-1.1.2-1.6L20.4 3.2c.9-.3 1.6.2 1.3 1.1Z"/></svg></a>
                    @endif
                    @if(!empty($settings->social_links['linkedin']))
                        <a href="{{ $settings->social_links['linkedin'] }}" target="_blank" rel="noopener noreferrer" aria-label="LinkedIn" style="color:#0A66C2"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M5.2 3.5a2.2 2.2 0 1 0 0 4.4 2.2 2.2 0 0 0 0-4.4ZM3.4 9.5H7v11H3.4v-11Zm5.8 0h3.4V11h.1c.5-.9 1.6-1.9 3.4-1.9 3.6 0 4.3 2.3 4.3 5.3v6.1h-3.6v-5.4c0-1.3 0-3-1.9-3s-2.2 1.4-2.2 2.9v5.5H9.2v-11Z"/></svg></a>
                    @endif
                    @if(!empty($settings->social_links['x']))
                        <a href="{{ $settings->social_links['x'] }}" target="_blank" rel="noopener noreferrer" aria-label="X" style="color:#fff"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M18.9 2H22l-6.8 7.8L23.2 22h-6.3L12 14.6 5.5 22H2.3l7.3-8.4L1.8 2h6.5l4.5 6.8L18.9 2Zm-1.1 18h1.7L7.3 3.9H5.5L17.8 20Z"/></svg></a>
                    @endif
                    @if(!empty($settings->social_links['youtube']))
                        <a href="{{ $settings->social_links['youtube'] }}" target="_blank" rel="noopener noreferrer" aria-label="YouTube" style="color:#FF0033"><svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M23 7.1a3 3 0 0 0-2.1-2.1C19 4.5 12 4.5 12 4.5s-7 0-8.9.5A3 3 0 0 0 1 7.1 31 31 0 0 0 .5 12a31 31 0 0 0 .5 4.9A3 3 0 0 0 3.1 19c1.9.5 8.9.5 8.9.5s7 0 8.9-.5a3 3 0 0 0 2.1-2.1 31 31 0 0 0 .5-4.9 31 31 0 0 0-.5-4.9ZM9.8 15.5v-7l6 3.5-6 3.5Z"/></svg></a>
                    @endif
                </div>
            </div>
        </div>
        <div class="cp">© {{ now()->year }} {{ $settings->site_name }} · تمامی حقوق محفوظ است.</div>
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
