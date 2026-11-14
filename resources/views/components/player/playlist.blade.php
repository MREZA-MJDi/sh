@props(['chapters'])
<nav aria-label="فهرست جلسات" class="space-y-6">
    @foreach($chapters as $chapter)
        <div>
            <h3 class="mb-2 font-black text-[#16202A]">{{ $chapter['title'] }}</h3>
            <ul class="space-y-1">
                @foreach($chapter['lessons'] as $lesson)
                    <li>
                        <button type="button" data-lesson
                                data-title="{{ $lesson['title'] }}" data-src="{{ $lesson['src'] }}"
                                class="group flex w-full items-center gap-3 rounded-lg px-3 py-3 text-right text-[#16202A] transition-colors hover:bg-[#F1F4F5] aria-[current=true]:bg-[#E3F1EC] aria-[current=true]:text-[#0B6553]">
                            <span data-check
                                  class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full border border-[#B5C0C6] text-xs group-data-[done]:border-[#0E7C66] group-data-[done]:bg-[#0E7C66] group-data-[done]:text-white"></span>
                            <span class="flex-1 text-sm font-medium">{{ $lesson['title'] }}</span>
                            <span class="text-xs tabular-nums text-[#4A5560]">{{ $lesson['duration'] }}</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </div>
    @endforeach
</nav>
