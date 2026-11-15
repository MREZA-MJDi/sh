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
            <dd>{{ $license ? (['active' => 'فعال', 'waiting_contract' => 'در انتظار اتصال سرویس', 'queued' => 'در صف صدور', 'failed' => 'ناموفق', 'revoked' => 'لغوشده'][$license->status] ?? $license->status) : 'برای این حساب هنوز درخواستی ثبت نشده است.' }}</dd>
        </dl>
        @if ((! $license || in_array($license->status, ['failed', 'revoked'], true)) && filled($video->course?->spotplayer_course_id))
            <form method="POST" action="{{ route('student.courses.spotplayer-license.store', $video->course_id) }}">
                @csrf
                <button class="btn s" type="submit">درخواست صدور لایسنس</button>
            </form>
        @endif
    </section>
@endsection
