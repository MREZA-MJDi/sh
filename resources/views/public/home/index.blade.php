<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#172554">
    <title>{{ $settings->default_title }}{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="{{ $settings->default_description }}">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="{{ rtrim($settings->canonical_base_url ?: url('/'), '/') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
    <x-public.layout.navbar />

    <main>
        <x-public.home.hero :banners="$banners" />
        <x-public.home.courses :courses="$courses" />
        <x-public.home.articles :articles="$articles" />
        <x-public.home.cta />
    </main>

    <x-public.layout.footer />
</body>
</html>
