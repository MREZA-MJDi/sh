<!doctype html>
<html lang="fa" dir="rtl">
<head>
    @php($canonicalBase = rtrim($settings->canonical_base_url ?: url('/'), '/'))
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    <title>@yield('seo-title', $settings->default_title){{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="@yield('seo-description', $settings->default_description)">
    <meta name="keywords" content="@yield('seo-keywords', implode(', ', $settings->default_keywords ?? []))">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    @if($settings->google_site_verification)<meta name="google-site-verification" content="{{ $settings->google_site_verification }}">@endif
    @if($settings->bing_site_verification)<meta name="msvalidate.01" content="{{ $settings->bing_site_verification }}">@endif
    <link rel="canonical" href="@yield('seo-canonical', $canonicalBase.request()->getPathInfo())">
    <meta property="og:site_name" content="{{ $settings->site_name }}">
    <meta property="og:type" content="@yield('og-type', 'website')">
    <meta property="og:title" content="@yield('seo-title', $settings->default_title)">
    <meta property="og:description" content="@yield('seo-description', $settings->default_description)">
    <meta property="og:url" content="@yield('seo-canonical', $canonicalBase.request()->getPathInfo())">
    <meta property="og:image" content="@yield('seo-image', $settings->default_og_image_path ? Storage::disk('public')->url($settings->default_og_image_path) : '')">
    @if($settings->twitter_handle)<meta name="twitter:site" content="{{ '@'.ltrim($settings->twitter_handle, '@') }}">@endif
    <meta name="twitter:card" content="summary_large_image">
    @vite(['resources/css/seo/public.css'])
    @stack('head')
</head>
<body>
    <header class="seo-header">
        <a class="seo-brand" href="{{ route('seo.articles.index') }}">{{ $settings->site_name }}</a>
        <nav><a href="{{ route('seo.articles.index') }}">مقاله‌ها</a><a href="{{ route('login') }}">ورود به سامانه</a></nav>
    </header>
    <main class="seo-container">
        @yield('content')
    </main>
    <footer class="seo-footer"><span>© {{ now()->year }} {{ $settings->site_name }}</span><a href="{{ route('seo.sitemap') }}">نقشه سایت</a></footer>
</body>
</html>
