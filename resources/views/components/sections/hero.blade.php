@props(['course'])
<section class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-16 md:grid-cols-2 md:py-20">
    <div>
        <x-ui.badge>دوره آموزشی</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black leading-[1.4] text-[#16202A] md:text-5xl">{{ $course['title'] }}</h1>
        @if($course['subtitle'])
            <p class="mt-5 max-w-md leading-8 text-[#4A5560]">{{ $course['subtitle'] }}</p>
        @endif
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button :href="route('login')">ورود برای دسترسی آموزشی</x-ui.button>
            <x-ui.button variant="outline" href="#curriculum">مشاهده سرفصل‌ها</x-ui.button>
        </div>
    </div>
    <ol class="rounded-2xl bg-[#16202A] p-8 text-white">
        @forelse($course['tracks'] as $track)
            <li class="flex items-center justify-between gap-4 border-b border-white/15 py-4 last:border-0">
                <span class="font-bold">{{ $track['name'] }}</span>
                <span class="shrink-0 text-sm text-white/70">{{ $track['lessons'] }} درس</span>
            </li>
        @empty
            <li class="py-4 text-sm leading-8 text-white/70">سرفصل‌های این دوره هنوز منتشر نشده‌اند.</li>
        @endforelse
    </ol>
</section>
