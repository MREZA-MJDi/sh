@props(['src'])
<div data-video-player tabindex="0"
     class="group relative aspect-video overflow-hidden rounded-xl bg-black focus-visible:outline-2 focus-visible:outline-[#0E7C66]">
    <video src="{{ $src }}" class="h-full w-full" playsinline></video>

    <button type="button" data-big-play aria-label="پخش"
            class="absolute inset-0 m-auto h-16 w-16 rounded-full bg-white/90 text-2xl text-[#16202A]">▶</button>

    <div dir="ltr"
         class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-black/80 to-transparent p-3 text-white md:opacity-0 md:transition-opacity md:group-hover:opacity-100 md:group-focus-within:opacity-100">
        <input type="range" data-seek min="0" max="0" step="0.1" value="0" aria-label="موقعیت ویدیو"
               class="w-full accent-[#0E7C66]">
        <div class="mt-1 flex items-center gap-4 text-sm">
            <button type="button" data-toggle aria-label="پخش و توقف" class="w-6">▶</button>
            <button type="button" data-back aria-label="۱۰ ثانیه عقب">⟲ 10</button>
            <button type="button" data-forward aria-label="۱۰ ثانیه جلو">10 ⟳</button>
            <span data-time class="tabular-nums">00:00 / 00:00</span>
            <span class="flex-1"></span>
            <input type="range" data-volume min="0" max="1" step="0.05" value="1" aria-label="صدا"
                   class="w-20 accent-[#0E7C66]">
            <button type="button" data-rate aria-label="سرعت پخش" class="w-12 tabular-nums">1x</button>
            <button type="button" data-fullscreen aria-label="تمام‌صفحه">⛶</button>
        </div>
    </div>
</div>
