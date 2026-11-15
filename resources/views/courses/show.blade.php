@extends('layouts.app')

@section('title', $course->title ?? $course->name)

@section('content')
@php
    $title = $course->title ?? $course->name;
    $lessons = $course->sections->flatMap->lessons;
    $minutes = intdiv($lessons->sum('duration_seconds'), 60);
    $schema = array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'Course',
        'name' => $title,
        'description' => \Illuminate\Support\Str::limit(strip_tags((string) ($course->description ?? '')), 300),
        'provider' => ['@type' => 'Organization', 'name' => config('app.name')],
        'offers' => ['@type' => 'Offer', 'price' => $course->isFree() ? 0 : (int) $course->price * 10, 'priceCurrency' => 'IRR', 'availability' => 'https://schema.org/InStock'],
    ]);
    $videoSchema = ($course->teaser_status === 'ready' && $course->teaser_url) ? array_filter([
        '@context' => 'https://schema.org',
        '@type' => 'VideoObject',
        'name' => 'تیزر دوره ' . $title,
        'description' => 'معرفی دوره ' . $title,
        'thumbnailUrl' => $course->teaser_poster_url,
        'contentUrl' => $course->teaser_url,
        'uploadDate' => optional($course->created_at)->toIso8601String(),
    ]) : null;
@endphp

<script type="application/ld+json">{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@if ($videoSchema)
    <script type="application/ld+json">{!! json_encode($videoSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
@endif

<div class="course-page" dir="rtl" style="display:grid;gap:24px;grid-template-columns:minmax(0,2fr) minmax(0,1fr);align-items:start">
    <main>
        <h1>{{ $title }}</h1>
        <p style="color:#64748b">{{ $lessons->count() }} درس · {{ $minutes }} دقیقه</p>

        {{-- تیزر: فقط پوستر لود می‌شود؛ خود ویدیو بعد از کلیک --}}
        @if ($course->teaser_status === 'ready' && $course->teaser_url)
            <div id="teaser" data-src="{{ $course->teaser_url }}" data-poster="{{ $course->teaser_poster_url }}"
                 style="position:relative;aspect-ratio:16/9;background:#000;border-radius:16px;overflow:hidden;cursor:pointer">
                @if ($course->teaser_poster_url)
                    <img src="{{ $course->teaser_poster_url }}" alt="تیزر {{ $title }}" loading="lazy" width="1280" height="720"
                         style="width:100%;height:100%;object-fit:cover">
                @endif
                <button type="button" aria-label="پخش تیزر"
                        style="position:absolute;inset:0;margin:auto;width:72px;height:72px;border-radius:50%;border:0;font-size:28px;cursor:pointer">▶</button>
            </div>
            <script>
                document.getElementById('teaser').addEventListener('click', function () {
                    var box = this, v = document.createElement('video');
                    v.src = box.dataset.src; v.controls = true; v.autoplay = true; v.playsInline = true;
                    v.preload = 'auto'; v.controlsList = 'nodownload';
                    if (box.dataset.poster) v.poster = box.dataset.poster;
                    v.style.cssText = 'width:100%;height:100%;background:#000';
                    box.innerHTML = ''; box.appendChild(v); box.style.cursor = 'default';
                }, {once: true});
            </script>
        @endif

        <nav role="tablist" style="display:flex;gap:16px;margin:24px 0 12px;border-bottom:1px solid #e2e8f0">
            <button role="tab" type="button" data-tab="about" aria-selected="true">معرفی</button>
            <button role="tab" type="button" data-tab="curriculum" aria-selected="false">سرفصل‌ها</button>
        </nav>

        {{-- هر دو تب در HTML هستند (برای سئو)؛ فقط با hidden پنهان می‌شوند --}}
        <section id="tab-about">{!! $course->description !!}</section>
        <section id="tab-curriculum" hidden>
            @include('courses._curriculum')
        </section>

        <script>
            document.querySelectorAll('[data-tab]').forEach(function (b) {
                b.addEventListener('click', function () {
                    document.querySelectorAll('[data-tab]').forEach(function (x) {
                        x.setAttribute('aria-selected', x === b ? 'true' : 'false');
                        document.getElementById('tab-' + x.dataset.tab).hidden = x !== b;
                    });
                });
            });
            if (location.hash === '#curriculum') document.querySelector('[data-tab=curriculum]').click();
        </script>
    </main>

    @include('courses._access_box')
</div>
@endsection
