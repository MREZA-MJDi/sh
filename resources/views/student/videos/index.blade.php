@extends('student.layouts.app')
@section('title', 'ویدئوهای ضبط‌شده')
@section('content')
<section class="page">
    <div class="card">
        <div class="chead"><h2>ویدئوهای ضبط‌شده کلاس‌های من</h2><span class="pill">{{ number_format($videos->total()) }} ویدئو</span></div>
        <p>فقط ویدئوهای دوره‌ها و کلاس‌هایی نمایش داده می‌شوند که دسترسی آموزشی فعال دارید.</p>
        <div class="g3">
            @forelse($videos as $video)
                <article class="card cc">
                    <div class="th"><svg class="i"><use href="#i-video"/></svg></div>
                    <h3>{{ $video->title }}</h3>
                    <p>{{ $video->course?->title ?? 'دوره آموزشی' }} · {{ $video->classroom?->name ?? 'همه کلاس‌های دوره' }}</p>
                    @if(filled($video->file_path))
                        <a class="btn s" href="{{ route('student.videos.show', $video) }}">مشاهده ویدئو</a>
                    @else
                        <span class="tag">ویدئوی متصل به SpotPlayer؛ اتصال سرویس در انتظار قرارداد رسمی</span>
                    @endif
                </article>
            @empty
                <p>هنوز ویدئوی منتشرشده‌ای برای دوره‌ها و کلاس‌های فعال شما ثبت نشده است.</p>
            @endforelse
        </div>
        {{ $videos->links() }}
    </div>
</section>
@endsection
