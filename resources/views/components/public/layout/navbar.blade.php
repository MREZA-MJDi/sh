@php($current = request()->route()?->getName())
<header class="sticky top-0 z-50 border-b border-[#D9E0E4] bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
        <a href="{{ route('home') }}" class="flex items-center gap-3" aria-label="آکادمی شیخان، صفحه اصلی">
            <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0E7C66] text-lg font-black text-white">ش</span>
            <span class="text-lg font-black tracking-tight text-[#16202A]">آکادمی شیخان</span>
        </a>

        <nav class="hidden items-center gap-7 text-sm font-bold text-[#4A5560] md:flex" aria-label="ناوبری اصلی">
            <a href="{{ route('home') }}" @class(['text-[#0E7C66]' => $current === 'home', 'transition hover:text-[#0E7C66]' => $current !== 'home'])>خانه</a>
            <a href="{{ route('public.courses.index') }}" @class(['text-[#0E7C66]' => str_starts_with($current ?? '', 'public.courses.'), 'transition hover:text-[#0E7C66]' => !str_starts_with($current ?? '', 'public.courses.')])>دوره‌ها</a>
            <a href="{{ route('seo.articles.index') }}" @class(['text-[#0E7C66]' => str_starts_with($current ?? '', 'seo.articles.'), 'transition hover:text-[#0E7C66]' => !str_starts_with($current ?? '', 'seo.articles.')])>مقالات</a>
        </nav>

        <div class="hidden items-center gap-3 md:flex">
            <a href="{{ route('login') }}" class="rounded-xl px-4 py-2.5 text-sm font-bold text-[#16202A] transition hover:bg-[#EEF3F1]">ورود</a>
            <a href="{{ route('register') }}" class="rounded-xl bg-[#0E7C66] px-5 py-2.5 text-sm font-bold text-white transition hover:bg-[#0B6553] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-[#0E7C66]">ثبت‌نام</a>
        </div>

        <details class="relative md:hidden">
            <summary class="flex cursor-pointer list-none items-center gap-2 rounded-xl border border-[#D9E0E4] px-3 py-2 text-sm font-bold text-[#16202A] [&::-webkit-details-marker]:hidden">
                <span>منو</span><span aria-hidden="true">☰</span>
            </summary>
            <nav class="absolute left-0 top-14 z-50 flex min-w-52 flex-col gap-1 rounded-2xl border border-[#D9E0E4] bg-white p-2 text-sm font-bold text-[#16202A] shadow-xl" aria-label="ناوبری موبایل">
                <a class="rounded-xl px-4 py-3 hover:bg-[#EEF3F1]" href="{{ route('home') }}">خانه</a>
                <a class="rounded-xl px-4 py-3 hover:bg-[#EEF3F1]" href="{{ route('public.courses.index') }}">دوره‌ها</a>
                <a class="rounded-xl px-4 py-3 hover:bg-[#EEF3F1]" href="{{ route('seo.articles.index') }}">مقالات</a>
                <a class="rounded-xl px-4 py-3 hover:bg-[#EEF3F1]" href="{{ route('login') }}">ورود</a>
                <a class="rounded-xl bg-[#0E7C66] px-4 py-3 text-white hover:bg-[#0B6553]" href="{{ route('register') }}">ثبت‌نام</a>
            </nav>
        </details>
    </div>
</header>
