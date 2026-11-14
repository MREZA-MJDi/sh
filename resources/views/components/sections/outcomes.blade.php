@props(['outcomes'])
<section class="bg-[#EEF3F1] py-20">
    <div class="mx-auto max-w-6xl px-5">
        <x-ui.section-title title="در این دوره چه یاد می‌گیرید؟" />
        <ul class="grid gap-x-10 gap-y-5 md:grid-cols-2">
            @foreach($outcomes as $item)
                <li class="flex gap-3 leading-8 text-[#16202A]">
                    <span class="mt-2 h-2.5 w-2.5 shrink-0 rounded-full bg-[#0E7C66]"></span>
                    {{ $item }}
                </li>
            @endforeach
        </ul>
    </div>
</section>
