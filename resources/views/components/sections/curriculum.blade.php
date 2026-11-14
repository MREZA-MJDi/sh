@props(['curriculum'])
<section id="curriculum" class="mx-auto max-w-4xl px-5 py-20">
    <x-ui.section-title title="سرفصل‌های دوره" desc="هر فصل شامل آموزش و یک پروژهٔ عملی است." />
    @foreach($curriculum as $chapter)
        <x-ui.accordion-item :title="$chapter['title']" name="curriculum" :open="$loop->first">
            <ul class="list-inside list-disc space-y-1">
                @foreach($chapter['lessons'] as $lesson)
                    <li>{{ $lesson }}</li>
                @endforeach
            </ul>
        </x-ui.accordion-item>
    @endforeach
</section>
