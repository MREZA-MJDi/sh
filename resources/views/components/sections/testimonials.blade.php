@props(['testimonials'])
<section class="mx-auto max-w-6xl px-5 py-20">
    <x-ui.section-title title="نظر دانشجویان" />
    <div class="grid gap-6 md:grid-cols-3">
        @foreach($testimonials as $t)
            <blockquote class="border-r-4 border-[#0E7C66] pr-5">
                <p class="leading-8 text-[#16202A]">{{ $t['text'] }}</p>
                <footer class="mt-3 text-sm text-[#4A5560]">{{ $t['name'] }}</footer>
            </blockquote>
        @endforeach
    </div>
</section>
