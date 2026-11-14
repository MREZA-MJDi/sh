<header class="sticky top-0 z-40 border-b border-slate-200 bg-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-5 py-4">
        <a href="{{ route('home') }}" class="text-xl font-black text-indigo-700">آکادمی شیخان</a>
        <nav class="hidden items-center gap-6 text-sm font-bold text-slate-600 md:flex" aria-label="ناوبری اصلی">
            <a href="{{ route('public.courses.index') }}" class="hover:text-indigo-700">دوره‌ها</a>
            <a href="{{ route('seo.articles.index') }}" class="hover:text-indigo-700">مقالات</a>
            <a href="{{ route('login') }}" class="hover:text-indigo-700">ورود</a>
        </nav>
        <a href="{{ route('register') }}" class="rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-700">ثبت‌نام</a>
    </div>
</header>
