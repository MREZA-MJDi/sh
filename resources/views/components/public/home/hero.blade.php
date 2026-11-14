@props(['banners'])

<section class="bg-[#16202A] text-white">
    <div class="mx-auto max-w-7xl px-5 py-12 md:py-20">
        @if($banners->isNotEmpty())
            <div class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth pb-4" aria-label="بنرهای صفحه اصلی">
                @foreach($banners as $banner)
                    <article class="relative min-w-full snap-center overflow-hidden rounded-3xl bg-[#263642] md:min-w-[85%]">
                        @if($banner->target_url)<a href="{{ $banner->target_url }}" @if($banner->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block">@endif
                            <img src="{{ Storage::disk('public')->url($banner->image_path) }}" alt="{{ $banner->alt_text }}" class="h-[320px] w-full object-cover md:h-[480px]" @if(!$loop->first) loading="lazy" @else fetchpriority="high" @endif>
                            <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#16202A]/95 via-[#16202A]/45 to-transparent p-6 pt-20 md:p-10 md:pt-28">
                                <h1 class="max-w-3xl text-2xl font-black leading-relaxed md:text-4xl">{{ $banner->title }}</h1>
                            </div>
                        @if($banner->target_url)</a>@endif
                    </article>
                @endforeach
            </div>
            @if($banners->count() > 1)<p class="mt-2 text-center text-xs text-white/50">برای دیدن بنر بعدی، افقی حرکت کنید.</p>@endif
        @else
            <div class="grid min-h-[420px] items-center gap-10 py-10 md:grid-cols-2">
                <div>
                    <span class="font-bold text-[#75C9B5]">آکادمی شیخان</span>
                    <h1 class="mt-5 text-4xl font-black leading-[1.5] md:text-5xl">یادگیری هدفمند، قدم‌به‌قدم تا پیشرفت</h1>
                    <p class="mt-5 max-w-xl leading-8 text-white/70">دوره‌های آموزشی را ببین، سرفصل‌ها را بررسی کن و مسیر یادگیری مناسب خودت را پیدا کن.</p>
                    <a href="{{ route('public.courses.index') }}" class="mt-8 inline-flex rounded-xl bg-[#0E7C66] px-6 py-3 font-bold text-white transition hover:bg-[#0B6553]">مشاهده دوره‌ها</a>
                </div>
                <div class="rounded-3xl border border-white/10 bg-white/[0.06] p-8 md:p-10">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-[#0E7C66] text-2xl">✦</div>
                    <p class="mt-6 text-xl font-black leading-9">از یادگیری تا مهارت، یک مسیر روشن.</p>
                    <p class="mt-3 text-sm leading-8 text-white/65">دوره‌ها و محتوای آموزشی منتشرشده در آکادمی را یک‌جا دنبال کن.</p>
                    <div class="mt-8 h-1.5 overflow-hidden rounded-full bg-white/10"><div class="h-full w-2/3 rounded-full bg-[#75C9B5]"></div></div>
                </div>
            </div>
        @endif
    </div>
</section>
