@props(['courses'])

<section class="py-16 md:py-20" aria-labelledby="home-courses-title">
    <div class="mx-auto max-w-7xl px-5">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <span class="text-sm font-bold text-indigo-600">یادگیری را شروع کن</span>
                <h2 id="home-courses-title" class="mt-2 text-3xl font-black">دوره‌های منتشرشده</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">فقط دوره‌هایی نمایش داده می‌شوند که در پنل مدیریت منتشر شده‌اند.</p>
            </div>
            <a href="{{ route('public.courses.index') }}" class="font-bold text-indigo-700 hover:text-indigo-900">همه دوره‌ها ←</a>
        </div>

        @if($courses->isNotEmpty())
            <div class="mt-9 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach($courses as $course)
                    @php
                        $lessonCount = $course->sections->sum('lessons_count');
                        $priceLabel = $course->type === 'paid'
                            ? number_format((int) $course->price).' تومان'
                            : 'رایگان';
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
        @else
            <div class="mt-9 rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center">
                <h3 class="font-bold">هنوز دوره‌ای منتشر نشده است</h3>
                <p class="mt-2 text-sm text-slate-500">به‌محض انتشار دوره از پنل مدیریت، این بخش به‌صورت خودکار به‌روزرسانی می‌شود.</p>
            </div>
        @endif
    </div>
</section>
