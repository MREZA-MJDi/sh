<footer class="mt-20 bg-slate-950 text-white">
    <div class="mx-auto grid max-w-7xl gap-8 px-5 py-12 md:grid-cols-3">
        <div>
            <a href="{{ route('home') }}" class="text-xl font-black">آکادمی شیخان</a>
            <p class="mt-4 max-w-sm text-sm leading-8 text-slate-400">مسیر یادگیری و پیشرفت، با محتوای آموزشی ساختارمند.</p>
        </div>
        <div>
            <h2 class="font-bold">دسترسی سریع</h2>
            <div class="mt-4 flex flex-col gap-3 text-sm text-slate-400">
                <a href="{{ route('public.courses.index') }}">همه دوره‌ها</a>
                <a href="{{ route('seo.articles.index') }}">مقالات</a>
            </div>
        </div>
        <div>
            <h2 class="font-bold">حساب کاربری</h2>
            <div class="mt-4 flex flex-col gap-3 text-sm text-slate-400">
                <a href="{{ route('login') }}">ورود</a>
                <a href="{{ route('register') }}">ساخت حساب</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10 px-5 py-5 text-center text-xs text-slate-500">© {{ now()->year }} آکادمی شیخان</div>
</footer>
