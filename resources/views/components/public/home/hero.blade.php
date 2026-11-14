@props(['banners'])

<section class="bg-slate-950 text-white">
    <div class="mx-auto max-w-7xl px-5 py-12 md:py-20">
        @if($banners->isNotEmpty())
            <div class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth pb-4" aria-label="بنرهای صفحه اصلی">
                @foreach($banners as $banner)
                    <article class="relative min-w-full snap-center overflow-hidden rounded-3xl bg-slate-800 md:min-w-[85%]">
                        @if($banner->target_url)
                            <a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block">
                        @endif
                            <img
                                src="{{ Storage::disk('public')->url($banner->image_path) }}"
                                alt="{{ $banner->alt_text }}"
                                class="h-[320px] w-full object-cover md:h-[480px]"
                                @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif
                            >
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-950/90 to-transparent p-6 pt-20 md:p-10 md:pt-28">
                                <h1 class="max-w-3xl text-2xl font-black leading-relaxed md:text-4xl">{{ $banner->title }}</h1>
                            </div>
                        @if($banner->target_url)
                            </a>
                        @endif
                    </article>
                @endforeach
            </div>
            @if($banners->count() > 1)
                <p class="mt-2 text-center text-xs text-slate-400">برای دیدن بنر بعدی، افقی حرکت کنید.</p>
            @endif
        @else
            <div class="grid min-h-[420px] items-center gap-10 py-10 md:grid-cols-2">
                <div>
                    <span class="font-bold text-indigo-300">آکادمی شیخان</span>
                    <h1 class="mt-5 text-4xl font-black leading-[1.5] md:text-5xl">یادگیری هدفمند، قدم‌به‌قدم تا پیشرفت</h1>
                    <p class="mt-5 max-w-xl leading-8 text-slate-300">دوره‌های منتشرشده را ببین، سرفصل‌ها را بررسی کن و مسیر آموزشی مناسب خودت را پیدا کن.</p>
                    <a href="{{ route('public.courses.index') }}" class="mt-8 inline-flex rounded-xl bg-indigo-500 px-6 py-3 font-bold text-white hover:bg-indigo-400">مشاهده دوره‌ها</a>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/5 p-8">
                    <div class="text-6xl">✦</div>
                    <p class="mt-5 text-lg font-bold">محتوای واقعی، بر اساس دوره‌هایی که در پنل منتشر می‌شوند.</p>
                    <p class="mt-3 text-sm leading-7 text-slate-300">با انتشار دوره یا درس از پنل مدیریت، محتوای عمومی به‌روز خواهد شد.</p>
                </div>
            </div>
        @endif
    </div>
</section>
