@props(['instructor'])
<section class="bg-[#EEF3F1] py-20">
    <div class="mx-auto flex max-w-4xl flex-col items-center gap-8 px-5 md:flex-row">
        <div class="h-36 w-36 shrink-0 rounded-full bg-[#C9D8D2]" role="img" aria-label="تصویر مدرس"></div>
        <div>
            <h2 class="text-2xl font-black text-[#16202A]">{{ $instructor['name'] }}</h2>
            <p class="mt-1 font-medium text-[#0B6553]">{{ $instructor['role'] }}</p>
            <p class="mt-4 leading-8 text-[#4A5560]">{{ $instructor['bio'] }}</p>
        </div>
    </div>
</section>
