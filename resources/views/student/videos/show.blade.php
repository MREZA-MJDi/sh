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
            <video controls controlsList="nodownload" disablepictureinpicture playsinline preload="metadata"
                   style="display:block;width:100%;max-height:72vh;background:#0b1220;border-radius:16px">
                <source src="{{ route('student.videos.stream', $video) }}">
                مرورگر شما از پخش این ویدئو پشتیبانی نمی‌کند.
            </video>
            <p class="tag">پخش از مسیر احراز هویت‌شده و فقط برای دانش‌آموز دارای دسترسی فعال</p>
        @else
            <div class="card cc">
                <h3>پلیر SpotPlayer هنوز فعال نشده است</h3>
                <p>شناسه ویدئو ثبت شده، اما تا دریافت قرارداد رسمی و فعال‌شدن API، لینک پخش معتبر صادر نمی‌شود.</p>
            </div>
        @endif
    </div>
</section>
@endsection
