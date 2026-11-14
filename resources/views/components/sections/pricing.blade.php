@props(['course'])
<section class="bg-[#16202A] py-20 text-center text-white">
    <div class="mx-auto max-w-xl px-5">
        <h2 class="text-3xl font-black">همین امروز کسب‌وکارتان را شروع کنید</h2>
        <p class="mt-4 text-2xl font-bold">{{ $course['price'] }}</p>
        <x-ui.button class="mt-8" :href="'/course/player'">ثبت‌نام و دسترسی به دوره</x-ui.button>
    </div>
</section>
