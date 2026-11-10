@extends('layouts.student')

@section('title', 'ویدیوهای کلاس')

@section('content')
    <section class="card">
        <div class="chead">
            <div>
                <span class="student-section-eyebrow">جلسات ضبط‌شده</span>
                <h2>ویدیوهای کلاس‌های من</h2>
                <p class="mu">فقط ویدیوهای مرتبط با دوره‌های دارای ثبت‌نام فعال نمایش داده می‌شوند.</p>
            </div>
            <span class="pill">{{ \App\Support\PersianDate::toPersianDigits($videos->total()) }} ویدیو</span>
        </div>

        @if (! $health->available)
            <div class="info" role="status">
                پخش محافظت‌شده SpotPlayer فعلاً فعال نیست: {{ $health->message }}
                فایل خام ویدئو از سایت ارائه نمی‌شود.
            </div>
        @endif

        <div class="student-video-list">
            @forelse ($videos as $video)
                <article class="student-video-item">
                    <div class="student-video-item__icon"><x-icon name="video"/></div>
                    <div class="student-video-item__body">
                        <h3>{{ $video->title }}</h3>
                        <p class="mu">
                            {{ $video->course?->title ?? 'دوره آموزشی' }}
                            @if ($video->classroom)
                                · {{ $video->classroom->name }}
                            @endif
                        </p>
                        <span class="tag w">
                            {{ $video->status === 'active' ? 'در انتظار تأیید لایسنس پخش' : 'در انتظار اتصال SpotPlayer' }}
                        </span>
                    </div>
                    <a class="btn g s" href="{{ route('student.videos.show', $video) }}">جزئیات و وضعیت پخش</a>
                </article>
            @empty
                <div class="empty">ویدیوی ضبط‌شده‌ای برای دوره‌ها و کلاس‌های ثبت‌نام‌شده‌ات منتشر نشده است.</div>
            @endforelse
        </div>

        @if ($videos->hasPages())
            <div class="frm">{{ $videos->links() }}</div>
        @endif
    </section>
@endsection
