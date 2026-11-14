@props(['courses'])

<section class="bg-[#F7F9F8] py-16 md:py-20" aria-labelledby="home-courses-title">
    <div class="mx-auto max-w-7xl px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="text-sm font-bold text-[#0E7C66]">یادگیری را شروع کن</span>
                <h2 id="home-courses-title" class="mt-2 text-3xl font-black text-[#16202A]">دوره‌های منتخب</h2>
                <p class="mt-3 text-sm leading-7 text-[#4A5560]">دوره‌های منتشرشده‌ی آکادمی را بررسی کن و مسیر یادگیری‌ات را انتخاب کن.</p>
            </div>
            <a href="{{ route('public.courses.index') }}" class="font-bold text-[#0E7C66] transition hover:text-[#0B6553]">مشاهده همه دوره‌ها ←</a>
        </div>
        @if($courses->isNotEmpty())
            <div class="mt-9 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    <x-public.courses.card :course="$course" :lessons="$course->sections->sum('lessons_count')" :href="route('public.courses.show', $course->slug)" />
                @endforeach
            </div>
        @else
            <div class="mt-9 rounded-2xl border border-dashed border-[#C9D8D2] bg-white p-10 text-center">
                <h3 class="font-bold text-[#16202A]">هنوز دوره‌ای منتشر نشده است</h3>
                <p class="mt-2 text-sm text-[#4A5560]">به‌محض انتشار دوره از پنل مدیریت، این بخش به‌روزرسانی می‌شود.</p>
            </div>
        @endif
    </div>
</section>
