@php
    // داده‌های نمونه؛ آدرس ویدیوها را با فایل‌های واقعی جایگزین کنید
    $sample = 'https://interactive-examples.mdn.mozilla.net/media/cc0-videos/flower.mp4';
    $chapters = [
        ['title' => 'فصل ۱: وردپرس', 'lessons' => [
            ['title' => 'معرفی دوره و نقشه راه', 'duration' => '05:12', 'src' => $sample],
            ['title' => 'نصب وردپرس روی هاست', 'duration' => '12:40', 'src' => $sample],
            ['title' => 'پوسته و المنتور', 'duration' => '18:03', 'src' => $sample],
        ]],
        ['title' => 'فصل ۲: سئو', 'lessons' => [
            ['title' => 'تحقیق کلمه کلیدی', 'duration' => '14:25', 'src' => $sample],
            ['title' => 'سئو داخلی صفحات', 'duration' => '16:48', 'src' => $sample],
        ]],
        ['title' => 'فصل ۳: مارکتینگ و اپلیکیشن', 'lessons' => [
            ['title' => 'ایمیل مارکتینگ', 'duration' => '11:30', 'src' => $sample],
            ['title' => 'ساخت اپلیکیشن از روی سایت', 'duration' => '20:10', 'src' => $sample],
        ]],
    ];
    $attachments = [
        ['name' => 'جزوه جلسه (PDF)', 'size' => '1.2 MB'],
        ['name' => 'فایل‌های تمرین (ZIP)', 'size' => '4.8 MB'],
    ];
    $comments = [
        ['name' => 'نام دانشجو', 'text' => 'آیا این روش روی هاست اشتراکی هم جواب می‌دهد؟'],
    ];
    $total = collect($chapters)->sum(fn ($c) => count($c['lessons']));
    $first = $chapters[0]['lessons'][0];
@endphp
<x-layouts.app title="پخش دوره">
    <div data-course-player class="mx-auto max-w-7xl px-5 py-8">
        <a href="/course" class="mb-4 inline-block text-sm font-medium text-[#0B6553]">بازگشت به صفحه دوره</a>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <div>
                <x-player.video-player :src="$first['src']" />

                <div class="mt-5 flex flex-wrap items-center justify-between gap-3">
                    <h1 data-lesson-title class="text-xl font-black text-[#16202A]">{{ $first['title'] }}</h1>
                    <div class="flex gap-2">
                        <button type="button" data-mark-done
                                class="rounded-lg border border-[#16202A] px-4 py-2 text-sm font-bold">علامت‌گذاری به‌عنوان دیده‌شده</button>
                        <button type="button" data-next
                                class="rounded-lg bg-[#0E7C66] px-4 py-2 text-sm font-bold text-white">جلسه بعد</button>
                    </div>
                </div>

                <div class="mt-8">
                    <x-player.lesson-tabs :attachments="$attachments" :comments="$comments" />
                </div>
            </div>

            <aside class="space-y-6 lg:sticky lg:top-6 lg:self-start">
                <x-player.course-progress :total="$total" />
                <x-player.playlist :chapters="$chapters" />
            </aside>
        </div>
    </div>
</x-layouts.app>
