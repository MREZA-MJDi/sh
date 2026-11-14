@props(['course', 'lessons' => 0, 'href'])

<article class="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-[#D9E0E4] bg-white shadow-sm transition duration-300 hover:-translate-y-1 hover:shadow-xl">
    <a href="{{ $href }}" aria-label="مشاهده دوره {{ $course->title }}">
        <div class="relative flex aspect-video items-center justify-center overflow-hidden bg-gradient-to-br from-[#E3F1EC] to-[#DCE7E3]">
            <span class="text-6xl font-black text-[#0E7C66]/70">ش</span>
            @if($course->subject)<span class="absolute right-4 top-4 rounded-full bg-white/95 px-3 py-1.5 text-xs font-bold text-[#0B6553] shadow-sm">{{ $course->subject }}</span>@endif
            @if($course->type === 'free')<span class="absolute left-4 top-4 rounded-full bg-[#0E7C66] px-3 py-1.5 text-xs font-bold text-white">رایگان</span>@endif
        </div>
    </a>
    <div class="flex flex-1 flex-col p-5">
        <div class="flex flex-wrap items-center gap-2">
            @if($course->grade)<span class="rounded-full bg-[#EEF3F1] px-3 py-1 text-xs font-bold text-[#4A5560]">{{ $course->grade }}</span>@endif
            <span class="ms-auto text-sm font-black text-[#0E7C66]">{{ $course->type === 'paid' ? number_format((int) $course->price).' تومان' : 'رایگان' }}</span>
        </div>
        <h3 class="mt-4 text-xl font-black leading-snug text-[#16202A]"><a href="{{ $href }}" class="transition hover:text-[#0E7C66]">{{ $course->title }}</a></h3>
        @if($course->description)<p class="mt-2 line-clamp-2 text-sm leading-7 text-[#4A5560]">{{ $course->description }}</p>@endif
        @if($course->teacher)<div class="mt-4 flex items-center gap-2 text-xs font-semibold text-[#4A5560]"><span class="flex h-8 w-8 items-center justify-center rounded-full bg-[#E3F1EC] font-black text-[#0E7C66]">م</span>{{ $course->teacher->name }}</div>@endif
        <div class="mt-auto flex items-center gap-3 border-t border-[#EEF1EF] pt-4 text-xs font-semibold text-[#4A5560]">
            <span>{{ $lessons }} درس منتشرشده</span>
            <a href="{{ $href }}" class="ms-auto font-black text-[#0E7C66]">جزئیات دوره ←</a>
        </div>
    </div>
</article>
