@props(['course'])
<section class="mx-auto grid max-w-6xl items-center gap-12 px-5 py-20 md:grid-cols-2">
    <div>
        <x-ui.badge>دوره پروژه‌محور</x-ui.badge>
        <h1 class="mt-5 text-4xl font-black leading-[1.4] text-[#16202A] md:text-5xl">{{ $course['title'] }}</h1>
        <p class="mt-5 max-w-md leading-8 text-[#4A5560]">{{ $course['subtitle'] }}</p>
        <div class="mt-8 flex flex-wrap gap-3">
            <x-ui.button :href="'/course/player'">مشاهده دوره</x-ui.button>
            <x-ui.button variant="outline" href="#curriculum">مشاهده سرفصل‌ها</x-ui.button>
        </div>
    </div>

    <ol class="rounded-2xl bg-[#16202A] p-8 text-white">
        @foreach($course['tracks'] as $track)
            <li class="flex items-center justify-between border-b border-white/15 py-4 last:border-0">
                <span class="font-bold">{{ $track['name'] }}</span>
                <span class="text-sm text-white/70">{{ $track['lessons'] }} جلسه</span>
            </li>
        @endforeach
    </ol>
</section>
