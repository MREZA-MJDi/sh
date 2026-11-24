@extends('student.layouts.app')
@section('title', 'ویدئوهای ضبط‌شده')
@section('content')
<section class="page">
<x-portal.page-head icon="video" tone="education" title="ویدئوهای ضبط‌شده"/>

    <div class="card">
        <div class="chead"><h2>ویدئوهای ضبط‌شده کلاس‌های من</h2><span class="pill">{{ number_format($videos->total()) }} ویدئو</span></div>
        <p>فقط ویدئوهای دوره‌ها و کلاس‌هایی نمایش داده می‌شوند که دسترسی آموزشی فعال دارید.</p>
        <div class="g3">
            @forelse($videos as $video)
                <article class="card cc">
                    <div class="th"><svg class="i"><use href="#i-video"/></svg></div>
                    <h3>{{ $video->title }}</h3>
                    <p>{{ $video->course?->title ?? 'دوره آموزشی' }} · {{ $video->classroom?->name ?? 'همه کلاس‌های دوره' }}@if($video->classSession) · {{ $video->classSession->title }}@endif</p>
                    @if(filled($video->file_path) && blank($video->external_id))
                        <a class="btn s" href="{{ route('student.videos.show', $video) }}">مشاهده ویدئو</a>
                    @else
                        <a class="btn s" href="{{ route('public.courses.show', $video->course->slug) }}">مشاهده در صفحه دوره</a>
                    @endif
                </article>
            @empty
                <x-portal.empty-state icon="video" title="هنوز ویدئویی برای شما منتشر نشده است." description="ویدئوهای آماده و تأییدشده کلاس‌های مجاز در همین صفحه نمایش داده می‌شوند."><a class="btn s" href="{{ route('student.courses.index') }}">مشاهده دوره‌های من</a></x-portal.empty-state>
            @endforelse
        </div>
        {{ $videos->links() }}
    </div>
</section>
@endsection
