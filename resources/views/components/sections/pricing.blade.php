@props(['course'])
<section class="bg-[#16202A] py-16 text-center text-white md:py-20">
    <div class="mx-auto max-w-xl px-5">
        <h2 class="text-3xl font-black">آماده‌ی شروع یادگیری هستی؟</h2>
        <p class="mt-4 text-2xl font-bold text-[#75C9B5]">{{ $course['price'] }}</p>
        <p class="mt-3 text-sm leading-7 text-white/60">برای ادامه و دسترسی به امکانات آموزشی وارد حساب کاربری‌ات شو.</p>
        <x-ui.button class="mt-8" :href="route('login')">ورود به حساب کاربری</x-ui.button>
    </div>
</section>
