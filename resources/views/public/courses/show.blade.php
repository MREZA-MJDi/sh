<!doctype html>
<html lang="fa" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $course->title }}{{ $settings->title_separator }}{{ $settings->site_name }}</title>
    <meta name="description" content="{{ \Illuminate\Support\Str::limit(strip_tags($course->description ?: $course->title), 155) }}">
    <meta name="robots" content="{{ $settings->robots_index ? 'index' : 'noindex' }},{{ $settings->robots_follow ? 'follow' : 'nofollow' }}">
    <link rel="canonical" href="{{ route('public.courses.show', $course->slug) }}">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;500;600;700;800&display=swap">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 font-sans text-slate-900">
<x-public.layout.navbar />
<main class="mx-auto max-w-5xl px-5 py-12">
    <a href="{{ route('public.courses.index') }}" class="text-sm font-bold text-indigo-700">← بازگشت به دوره‌ها</a>
    <header class="mt-6 rounded-3xl bg-slate-950 p-8 text-white md:p-12">
        @if($course->subject)<span class="text-sm font-bold text-indigo-300">{{ $course->subject }}</span>@endif
        <h1 class="mt-3 text-3xl font-black leading-relaxed md:text-4xl">{{ $course->title }}</h1>
        @if($course->description)<p class="mt-5 max-w-3xl leading-8 text-slate-300">{{ $course->description }}</p>@endif
        <div class="mt-7 flex flex-wrap gap-4 text-sm text-slate-300">
            @if($course->teacher)<span>مدرس: {{ $course->teacher->name }}</span>@endif
            <span>{{ $course->type === 'paid' ? number_format((int) $course->price).' تومان' : 'رایگان' }}</span>
            <span>{{ number_format($course->sections->sum(fn ($section) => $section->lessons->count())) }} درس منتشرشده</span>
        </div>
        <a href="{{ route('login') }}" class="mt-8 inline-flex rounded-xl bg-indigo-500 px-6 py-3 font-bold text-white hover:bg-indigo-400">ورود برای دسترسی آموزشی</a>
    </header>

    <section class="mt-12" aria-labelledby="curriculum-title">
        <h2 id="curriculum-title" class="text-2xl font-black">سرفصل‌های دوره</h2>
        @forelse($course->sections as $section)
            <article class="mt-5 rounded-2xl border border-slate-200 bg-white p-6">
                <h3 class="text-lg font-black">{{ $section->title }}</h3>
                @if($section->description)<p class="mt-2 text-sm leading-7 text-slate-500">{{ $section->description }}</p>@endif
                <ol class="mt-4 divide-y divide-slate-100">
                    @forelse($section->lessons as $lesson)
                        <li class="flex items-center justify-between gap-4 py-3 text-sm">
                            <span>{{ $loop->iteration }}. {{ $lesson->title }}</span>
                            @if($lesson->duration_minutes)<span class="shrink-0 text-xs text-slate-400">{{ $lesson->duration_minutes }} دقیقه</span>@endif
                        </li>
                    @empty
                        <li class="py-3 text-sm text-slate-400">درس منتشرشده‌ای در این بخش نیست.</li>
                    @endforelse
                </ol>
            </article>
        @empty
            <p class="mt-5 rounded-2xl bg-white p-6 text-slate-500">سرفصل‌های این دوره هنوز منتشر نشده‌اند.</p>
        @endforelse
    </section>
</main>
<x-public.layout.footer />
</body>
</html>
