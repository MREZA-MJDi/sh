@props(['title', 'desc' => null])
<div class="mb-10 max-w-2xl">
    <h2 class="text-3xl font-black text-[#16202A]">{{ $title }}</h2>
    @if($desc)
        <p class="mt-3 leading-8 text-[#4A5560]">{{ $desc }}</p>
    @endif
</div>
