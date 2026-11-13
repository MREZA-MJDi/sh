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
    <meta name="theme-color" content="#172554">
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
    @vite(['resources/css/seo/public.css'])
    @stack('head')
</head>
<body>
    <header class="seo-header">
        <a class="seo-brand" href="{{ route('home') }}">{{ $settings->site_name }}</a>
        <nav><a href="{{ route('home') }}#courses">دوره‌ها</a><a href="{{ route('seo.articles.index') }}">مجله</a><a href="{{ route('login') }}">ورود به سامانه</a></nav>
    </header>
    <main class="seo-container">
        @yield('content')
    </main>
    <footer class="seo-footer"><span>© {{ now()->year }} {{ $settings->site_name }}</span>@if($settings->sitemap_enabled)<a href="{{ route('seo.sitemap') }}">نقشه سایت</a>@endif</footer>
</body>
</html>
