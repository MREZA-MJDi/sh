@extends('teacher.layouts.app')
@section('title', 'داشبورد')
@section('content')
@include("components.announcement-feed", ["announcements" => $announcements ?? collect()])
<div class="teacher-page-head teacher-dashboard-hero">
    <div class="teacher-dashboard-identity">
        <div class="teacher-dashboard-avatar">
            @if(($teacher ?? auth()->user())->avatarUrl())
                <img src="{{ ($teacher ?? auth()->user())->avatarUrl() }}" alt="{{ data_get($teacher ?? auth()->user(), 'name') }}">
            @else
                <span class="avatar-letter">{{ mb_substr((string) data_get($teacher ?? auth()->user(), 'name', 'م'), 0, 1) }}</span>
            @endif
        </div>
        <div class="teacher-dashboard-copy">
            <div class="teacher-dashboard-badges">
                <span class="teacher-eyebrow">
                    <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.2" fill="none"><path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/></svg>
                    فضای کاری مدرس
                </span>
                @if(data_get($teacher ?? auth()->user(), 'subject'))
                    <span class="teacher-subject-tag">{{ data_get($teacher ?? auth()->user(), 'subject') }}</span>
                @endif
            </div>
            <h1>سلام {{ data_get($teacher ?? auth()->user(), 'name') }} 👋</h1>
            <p>کلاس‌ها، محتوا و ارتباط با خانواده‌ها را از یک محیط مدیریت کنید.</p>
        </div>
    </div>
    @if(auth()->user()->hasPermissionTo('consultations.create'))
        <div class="teacher-dashboard-actions">
            <a class="b teacher-cta-btn" href="{{ route('teacher.consultations.index') }}">
                <svg viewBox="0 0 24 24" width="16" height="16" stroke="currentColor" stroke-width="2" fill="none"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                <span>درخواست هماهنگی با مدیریت</span>
            </a>
        </div>
    @endif
</div>
<section class="teacher-dashboard-focus" aria-label="اولویت‌های کاری امروز"><div class="teacher-section-head"><div><h2>اولویت‌های کاری</h2><p>برای شروع سریع، موارد نیازمند پیگیری را باز کنید.</p></div></div><div class="teacher-focus-items">@if(auth()->user()->hasPermissionTo('homework.view.assigned'))<a href="{{ route('teacher.homework') }}" class="teacher-focus-item"><span class="teacher-focus-symbol">✎</span><span><strong>بررسی پاسخ تکالیف</strong><small>{{ $stats['homework_review'] }} پاسخ منتظر بررسی</small></span><span class="teacher-focus-arrow" aria-hidden="true">←</span></a>@endif @if(auth()->user()->hasPermissionTo('messages.view.assigned'))<a href="{{ route('teacher.messages.index') }}" class="teacher-focus-item"><span class="teacher-focus-symbol">✉</span><span><strong>پیام‌های تازه</strong><small>{{ $stats['new_messages'] }} گفت‌وگوی باز</small></span><span class="teacher-focus-arrow" aria-hidden="true">←</span></a>@endif @if(auth()->user()->hasPermissionTo('attendance.view.assigned'))<a href="{{ route('teacher.attendance') }}" class="teacher-focus-item"><span class="teacher-focus-symbol">✓</span><span><strong>حضور و غیاب</strong><small>{{ $stats['absent_today'] }} غیبت امروز</small></span><span class="teacher-focus-arrow" aria-hidden="true">←</span></a>@endif</div></section>
<div class="g g4 teacher-stat-grid">
    <div class="st am"><b>{{ $stats['classes_today'] }}</b><span>کلاس‌های تحت مدیریت</span><small>کلاس‌های فعال تخصیص‌یافته</small></div>
    <div class="st bl"><b>{{ $stats['homework_review'] }}</b><span>پاسخ در انتظار بررسی</span><small>نیازمند رسیدگی مدرس</small></div>
    <div class="st pk"><b>{{ $stats['absent_today'] }}</b><span>غایب امروز</span><small>در کلاس‌های شما</small></div>
    <div class="st"><b>{{ $stats['new_messages'] }}</b><span>پیام‌های باز</span><small>گفت‌وگوهای نیازمند پاسخ</small></div>
</div>
<section class="teacher-action-grid" aria-label="ابزارهای آموزشی">
@if(auth()->user()->hasPermissionTo('videos.manage.assigned'))<a class="teacher-action-card" href="{{ route('teacher.videos') }}"><span class="teacher-action-icon">▶</span><strong>بارگذاری ویدئو</strong><small>برای دوره یا کلاس تخصیص‌یافته</small></a>@endif
@if(auth()->user()->hasPermissionTo('resources.manage.assigned'))<a class="teacher-action-card" href="{{ route('teacher.resources') }}"><span class="teacher-action-icon">▤</span><strong>منابع و جزوه‌ها</strong><small>ثبت PDF، کتاب و فایل آموزشی</small></a>@endif
@if(auth()->user()->hasPermissionTo('homework.manage.assigned'))<a class="teacher-action-card" href="{{ route('teacher.homework') }}"><span class="teacher-action-icon">✎</span><strong>تعریف تکلیف</strong><small>متن تمرین یا فایل PDF</small></a>@endif
@if(auth()->user()->hasPermissionTo('messages.view.assigned'))<a class="teacher-action-card" href="{{ route('teacher.messages.create') }}"><span class="teacher-action-icon">✉</span><strong>مکاتبه با اولیا</strong><small>فقط خانواده‌های دانش‌آموزان مرتبط</small></a>@endif
</section>
<section class="c teacher-calendar-card"><div class="teacher-section-head"><div><h2>برنامه هفت روز آینده</h2><p>جلسه‌های ثبت‌شده در کلاس‌های تحت مدیریت شما</p></div>@if(auth()->user()->hasPermissionTo('classes.view.assigned'))<a class="b s" href="{{ route('teacher.meetings') }}">جلسات با اولیا</a>@endif</div>
<div class="teacher-week-calendar sh-calendar-grid" data-teacher-week-calendar>
@foreach(($weekCalendar ?? collect()) as $day)
<div class="teacher-week-day" data-gregorian-date="{{ $day['date'] }}"><div class="teacher-week-day-head"><span data-weekday>—</span><strong data-day-number>—</strong><small>{{ $day['count'] }} جلسه</small></div><div class="teacher-week-events">
@forelse($day['sessions'] as $session)<div class="teacher-week-event"><strong>{{ $session->title ?: $session->classroom?->name }}</strong><small>{{ $session->starts_at?->format('H:i') }} · {{ $session->classroom?->course?->title ?? $session->classroom?->name }}@if($session->classroom?->course?->grade) · پایه {{ $session->classroom->course->grade }}@endif @if($session->classroom?->course?->season) · {{ ['spring' => 'بهار', 'summer' => 'تابستان', 'autumn' => 'پاییز', 'winter' => 'زمستان'][$session->classroom->course->season] ?? $session->classroom->course->season }}@endif</small></div>
@empty<span class="teacher-no-event">بدون جلسه</span>@endforelse
</div></div>
@endforeach
</div></section>
<div class="g g2">
<section class="c"><div class="teacher-section-head"><h2>کلاس‌های تحت مدیریت شما</h2>@if(auth()->user()->hasPermissionTo('classes.view.assigned'))<a href="{{ route('teacher.classes') }}">همه کلاس‌ها</a>@endif</div>
@forelse(($todayClasses ?? []) as $cl)<div class="row"><div>{{ data_get($cl,'title') }}<small>{{ data_get($cl,'time') }}</small></div>@if(data_get($cl,'status')==='link')<a class="b s" href="{{ data_get($cl,'url') }}" target="_blank" rel="noopener noreferrer">ورود</a>@else<i class="bd am">لینک جلسه ثبت نشده</i>@endif</div>
@empty<p>کلاس فعالی برای حساب شما ثبت نشده است.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>جلسات امروز</h2>@if(auth()->user()->hasPermissionTo('classes.view.assigned'))<a href="{{ route('teacher.live') }}">کلاس آنلاین</a>@endif</div>
@forelse(($todaySessions ?? []) as $session)<div class="row"><div>{{ data_get($session,'title') }}<small>{{ data_get($session,'time') }}</small></div>@if(data_get($session,'status')==='link')<a class="b s" href="{{ data_get($session,'url') }}" target="_blank" rel="noopener noreferrer">ورود به کلاس</a>@elseif(data_get($session,'status')==='waiting_jitsi')<i class="bd am">لینک Jitsi ثبت نشده است</i>@elseif(data_get($session,'status')==='waiting')<i class="bd am">در انتظار فعال‌سازی Skyroom</i>@else<i class="bd bl">جلسه حضوری</i>@endif</div>
@empty<p>برای امروز جلسه‌ای زمان‌بندی نشده است.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>پاسخ‌های منتظر بررسی</h2>@if(auth()->user()->hasPermissionTo('homework.view.assigned'))<a href="{{ route('teacher.homework') }}">مدیریت تکالیف</a>@endif</div>
@forelse(($pendingHomework ?? []) as $item)<div class="row"><div>{{ data_get($item,'title') }}<small>{{ data_get($item,'submitted',0) }} پاسخ دانش‌آموز</small></div><i class="bd bl">در انتظار بررسی</i></div>
@empty<p>پاسخ بررسی‌نشده‌ای وجود ندارد.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>پیام‌های تازه</h2>@if(auth()->user()->hasPermissionTo('messages.view.assigned'))<a href="{{ route('teacher.messages.index') }}">صندوق پیام</a>@endif</div>
@forelse(($newMessages ?? []) as $message)<div class="row"><div>{{ data_get($message,'from') }}<small>{{ data_get($message,'text') }}</small></div><i class="bd pk">باز</i></div>
@empty<p>پیام بازی وجود ندارد.</p>@endforelse</section>
</div>
<section class="c teacher-attendance-summary">
    <div class="teacher-section-head"><div><h2>روند حضور هفت روز اخیر</h2><p>درصد حضور و تأخیر نسبت به کل رکوردهای ثبت‌شده در کلاس‌های شما</p></div>@if(auth()->user()->hasPermissionTo('attendance.view.assigned'))<a href="{{ route('teacher.attendance') }}">مشاهده سوابق</a>@endif</div>
    <div class="teacher-attendance-bars" role="list" aria-label="درصد حضور روزانه">
        @foreach(($weekAttendance ?? collect()) as $day)
            <div class="teacher-attendance-bar" role="listitem">
                <div class="teacher-attendance-bar-track" title="{{ $day['percent'] }}٪">
                    <span style="height:{{ max(0,min(100,(int) $day['percent'])) }}%"></span>
                </div>
                <strong>{{ $day['percent'] }}٪</strong>
                <small>{{ $day['label'] }}</small>
            </div>
        @endforeach
    </div>
</section>
@endsection