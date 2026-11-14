<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16202A">
    <title>{{ $course->title }}{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($course->description ?: $course->title), 155) }}">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="{{ route('public.courses.show', $course->slug) }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-white font-sans text-[#16202A]">
<x-public.layout.navbar />
<main>
    <div class="mx-auto max-w-6xl px-5 pt-8">
        <a href="{{ route('public.courses.index') }}" class="inline-flex items-center gap-2 text-sm font-bold text-[#0E7C66] transition hover:text-[#0B6553]">← بازگشت به همه دوره‌ها</a>
    </div>

    <x-sections.hero :course="$landingCourse" />
    @if(count($curriculum))
        <x-sections.curriculum :curriculum="$curriculum" />
    @endif
    @if($instructor)
        <x-sections.instructor :instructor="$instructor" />
    @endif
    <x-sections.pricing :course="$landingCourse" />
</main>
<x-public.layout.footer />
</body>
</html>
