@extends('layouts.app')

@section('title', $lesson->title)

@section('content')
<div dir="rtl" style="max-width:860px;margin:auto">
    <p><a href="{{ route('courses.show', $course->slug) }}#curriculum">← {{ $course->title ?? $course->name }}</a></p>
    <h1>{{ $lesson->title }}</h1>
    <p style="color:#64748b">درس {{ $index + 1 }} از {{ $total }}</p>

    @if ($embedUrl)
        <div style="aspect-ratio:16/9;background:#000;border-radius:16px;overflow:hidden">
            <iframe src="{{ $embedUrl }}" title="{{ $lesson->title }}" style="width:100%;height:100%;border:0"
                    allow="fullscreen" referrerpolicy="no-referrer" loading="lazy"></iframe>
        </div>
    @elseif ($state['state'] === \App\Services\Learning\CourseAccess::ACTIVE)
        <div style="border:1px dashed #94a3b8;border-radius:16px;padding:16px">
            <p><strong>این درس در پلیر SpotPlayer پخش می‌شود.</strong></p>
            <p>اپ را باز کنید، دوره را انتخاب کنید و «{{ $lesson->title }}» (درس {{ $index + 1 }}) را پخش کنید.</p>
        </div>
        @include('courses._license_box')
    @else
        <div style="border:1px solid #fde68a;background:#fffbeb;border-radius:16px;padding:16px">
            ثبت‌نام شما فعال است ولی لایسنس هنوز آماده نشده؛ <a href="{{ route('courses.show', $course->slug) }}">به صفحه دوره برگردید</a>.
        </div>
    @endif

    @if ($lesson->description) <div style="margin:16px 0">{{ $lesson->description }}</div> @endif

    <form method="POST" action="{{ route('lessons.complete', [$course->slug, $lesson->id]) }}" style="margin:16px 0">
        @csrf
        <button type="submit">{{ $done ? '✅ دیده‌شده (برای برگرداندن کلیک کنید)' : 'این درس را دیدم' }}</button>
    </form>

    <div style="display:flex;justify-content:space-between;gap:8px">
        @if ($prev) <a href="{{ route('lessons.show', [$course->slug, $prev->id]) }}">→ {{ $prev->title }}</a> @else <span></span> @endif
        @if ($next) <a href="{{ route('lessons.show', [$course->slug, $next->id]) }}">{{ $next->title }} ←</a> @endif
    </div>
</div>
@endsection
