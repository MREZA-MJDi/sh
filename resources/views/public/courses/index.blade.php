<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>دوره‌های آموزشی{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="فهرست دوره‌های منتشرشده آکادمی شیخان">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="{{ route('public.courses.index') }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
<x-public.layout.navbar />
<main class="mx-auto max-w-7xl px-5 py-14">
    <header class="max-w-2xl">
        <span class="font-bold text-indigo-600">آموزش</span>
        <h1 class="mt-3 text-3xl font-black">دوره‌های آموزشی</h1>
        <p class="mt-4 leading-8 text-slate-500">دوره‌هایی که وضعیت انتشار آن‌ها در پنل مدیریت روی «منتشرشده» قرار گرفته است.</p>
    </header>
    @if($courses->count())
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach($courses as $course)
                @php
                    $lessonCount = $course->sections->sum('lessons_count');
                    $priceLabel = $course->type === 'paid' ? number_format((int) $course->price).' تومان' : 'رایگان';
                @endphp
                <x-kit.education.course-card
                    :title="$course->title"
                    :description="$course->description"
                    :category="$course->subject"
                    :teacher="$course->teacher?->name"
                    :lessons="$lessonCount"
                    :price="$priceLabel"
                    :grades="$course->grade ? [$course->grade] : []"
                    :href="route('public.courses.show', $course->slug)"
                />
            @endforeach
        </div>
        <div class="mt-10">{{ $courses->links() }}</div>
    @else
        <div class="mt-10 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-500">در حال حاضر دورهٔ منتشرشده‌ای وجود ندارد.</div>
    @endif
</main>
<x-public.layout.footer />
</body>
</html>
