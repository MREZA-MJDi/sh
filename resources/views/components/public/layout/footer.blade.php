<footer class="mt-20 bg-[#16202A] text-white">
    <div class="mx-auto grid max-w-7xl gap-10 px-5 py-14 md:grid-cols-3">
        <div>
            <a href="{{ route('home') }}" class="flex items-center gap-3 text-xl font-black">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-[#0E7C66]">ش</span>
                آکادمی شیخان
            </a>
            <p class="mt-5 max-w-sm text-sm leading-8 text-white/65">مسیر یادگیری و پیشرفت، با محتوای آموزشی ساختارمند و دوره‌های کاربردی.</p>
        </div>
        <div>
            <h2 class="font-bold">دسترسی سریع</h2>
            <div class="mt-5 flex flex-col gap-3 text-sm text-white/65">
                <a class="transition hover:text-white" href="{{ route('home') }}">صفحه اصلی</a>
                <a class="transition hover:text-white" href="{{ route('public.courses.index') }}">همه دوره‌ها</a>
                <a class="transition hover:text-white" href="{{ route('seo.articles.index') }}">مقالات</a>
            </div>
        </div>
        <div>
            <h2 class="font-bold">حساب کاربری</h2>
            <div class="mt-5 flex flex-col gap-3 text-sm text-white/65">
                <a class="transition hover:text-white" href="{{ route('login') }}">ورود</a>
                <a class="transition hover:text-white" href="{{ route('register') }}">ساخت حساب</a>
            </div>
        </div>
    </div>
    <div class="border-t border-white/10 px-5 py-5 text-center text-xs text-white/45">© {{ now()->year }} آکادمی شیخان. تمامی حقوق محفوظ است.</div>
</footer>
