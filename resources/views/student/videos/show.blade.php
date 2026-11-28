@extends('student.layouts.app')
@section('title', $video->title)
@section('content')
<section class="page">
<x-portal.page-head icon="video" :title="$video->title" :back="route('student.videos.index')" back-label="بازگشت به ویدئوها"/>

    <div class="card">
        <div class="chead">
            <h2>پخش ویدئو</h2>
            
        </div>
        <p>{{ $video->course?->title ?? 'دوره آموزشی' }} · {{ $video->classroom?->name ?? 'همه کلاس‌های دوره' }}@if($video->classSession) · {{ $video->classSession->title }}@endif</p>
        @if($video->status === 'active' && filled($video->file_path) && blank($video->external_id))
            <div class="learning-video-frame" style="position:relative">
            <video controls controlsList="nodownload nofullscreen noremoteplayback" disablepictureinpicture disableremoteplayback data-protected-video playsinline preload="metadata"
                   style="display:block;width:100%;max-height:72vh;background:#0b1220;border-radius:16px">
                مرورگر شما از پخش این ویدئو پشتیبانی نمی‌کند.
            </video>
            <x-learning.video-watermark :source="route('student.videos.stream', $video)"/>
            </div>
            <p class="tag">تماشای آنلاین در شیخان</p>
        @elseif($video->status === 'active' && filled($video->external_id) && filled($video->embed_url))
            <a class="btn s" href="{{ route('public.courses.show', $video->course->slug) }}#course-video-{{ $video->id }}">
                تماشا در SpotPlayer
            </a>
        @else
            <div class="card cc">
                <h3>پلیر SpotPlayer هنوز فعال نشده است</h3>
                <p>شناسه ویدئو ثبت شده، اما تا دریافت قرارداد رسمی و فعال‌شدن API، لینک پخش معتبر صادر نمی‌شود.</p>
            </div>
        @endif
    </div>
</section>
@endsection
