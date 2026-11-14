@props(['title', 'name' => 'accordion', 'open' => false])
<details name="{{ $name }}" @if($open) open @endif class="group border-b border-[#D9E0E4] first:border-t">
    <summary class="flex cursor-pointer list-none items-center justify-between py-5 font-bold text-[#16202A] [&::-webkit-details-marker]:hidden">
        {{ $title }}
        <span class="transition-transform group-open:rotate-45">+</span>
    </summary>
    <div class="pb-5 leading-8 text-[#4A5560]">{{ $slot }}</div>
</details>
