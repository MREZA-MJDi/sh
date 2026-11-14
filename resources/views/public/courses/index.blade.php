<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#16202A">
    <title>دوره‌های آموزشی{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="فهرست دوره‌های آموزشی منتشرشده آکادمی شیخان">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="{{ route('public.courses.index') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800;900&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F7F9F8] font-sans text-[#16202A]">
<x-public.layout.navbar />
<main>
    <header class="bg-[#16202A] text-white">
        <div class="mx-auto max-w-7xl px-5 py-16 md:py-20">
            <span class="font-bold text-[#75C9B5]">مسیر یادگیری</span>
            <h1 class="mt-3 text-3xl font-black md:text-4xl">دوره‌های آموزشی</h1>
            <p class="mt-4 max-w-2xl text-sm leading-8 text-white/65">دوره‌های منتشرشده را بررسی کن، سرفصل‌ها را ببین و دوره مناسب خودت را انتخاب کن.</p>
        </div>
    </header>
    <section class="mx-auto max-w-7xl px-5 py-12 md:py-16" aria-label="فهرست دوره‌ها">
        @if($courses->count())
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    <x-public.courses.card :course="$course" :lessons="$course->sections->sum('lessons_count')" :href="route('public.courses.show', $course->slug)" />
                @endforeach
            </div>
            <div class="mt-10">{{ $courses->links() }}</div>
        @else
            <div class="rounded-2xl border border-dashed border-[#C9D8D2] bg-white p-10 text-center text-[#4A5560]">در حال حاضر دوره‌ی منتشرشده‌ای وجود ندارد.</div>
        @endif
    </section>
</main>
<x-public.layout.footer />
</body>
</html>
