@extends('layouts.student')

@section('title', $video->title)

@section('content')
    <a class="back" href="{{ route('student.videos.index') }}">← ویدیوهای کلاس</a>

    <section class="card">
        <span class="student-section-eyebrow">ویدئوی ضبط‌شده کلاس</span>
        <h2>{{ $video->title }}</h2>
        <p class="mu">
            {{ $video->course?->title ?? 'دوره آموزشی' }}
            @if ($video->classroom)
                · {{ $video->classroom->name }}
            @endif
        </p>

        <div class="student-video-player-placeholder" role="status">
            <x-icon name="lock"/>
            <strong>پخش محافظت‌شده هنوز آماده نیست</strong>
            <p>
                ویدئو فقط پس از تأیید اتصال API، نگاشت شناسه دوره و صدور لایسنس اختصاصی SpotPlayer
                داخل همین سایت پخش می‌شود. برای حفاظت از محتوای آموزشی، فایل خام قابل دریافت یا پخش مستقیم نیست.
            </p>
        </div>

        <dl class="dl">
            <dt>وضعیت فایل</dt>
            <dd>{{ $video->status === 'active' ? 'ثبت‌شده در سامانه' : 'در انتظار انتقال به SpotPlayer' }}</dd>
            <dt>وضعیت سرویس</dt>
            <dd>{{ $health->available ? 'سرویس در دسترس' : $health->message }}</dd>
            <dt>لایسنس این دوره</dt>
            <dd>{{ $license ? 'رکورد لایسنس فعال ثبت شده؛ پخش مرورگری هنوز به آداپتور رسمی نیاز دارد.' : 'برای این حساب لایسنس فعال ثبت نشده است.' }}</dd>
        </dl>
    </section>
@endsection
