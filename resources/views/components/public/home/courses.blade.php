@props(['courses'])

<section class="bg-[#F7F9F8] py-16 md:py-20" aria-labelledby="home-courses-title">
    <div class="mx-auto max-w-7xl px-5">
        <x-kit.layout.section-heading eyebrow="یادگیری را شروع کن" title="دوره‌های منتخب" description="دوره‌های منتشرشده‌ی آکادمی را بررسی کن و مسیر یادگیری‌ات را انتخاب کن." :href="route('public.courses.index')" link-label="مشاهده همه دوره‌ها" />
        @if($courses->isNotEmpty())
            <div class="mt-9 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    <x-public.courses.card
                        :course="$course"
                        :lessons="$course->sections->sum('lessons_count')"
                        :href="route('public.courses.show', $course->slug)"
                    />
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
