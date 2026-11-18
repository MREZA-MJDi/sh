@extends('teacher.layouts.app')
@section('title', 'داشبورد')
@section('content')
<div class="teacher-page-head"><div><span class="teacher-eyebrow">فضای کاری آموزشی</span><h1>سلام {{ data_get($teacher ?? auth()->user(), 'name') }} 👋</h1><p>کلاس‌ها، محتوا و ارتباط با خانواده‌ها را از یک محیط مدیریت کنید.</p></div><a class="b" href="{{ route('teacher.consultations.index') }}">درخواست هماهنگی با مدیریت</a></div>
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
<section class="c teacher-calendar-card"><div class="teacher-section-head"><div><h2>برنامه هفت روز آینده</h2><p>جلسه‌های ثبت‌شده در کلاس‌های تحت مدیریت شما</p></div><a class="b s" href="{{ route('teacher.meetings') }}">جلسات با اولیا</a></div>
<div class="teacher-week-calendar" data-teacher-week-calendar>
@foreach(($weekCalendar ?? collect()) as $day)
<div class="teacher-week-day" data-gregorian-date="{{ $day['date'] }}"><div class="teacher-week-day-head"><span data-weekday>—</span><strong data-day-number>—</strong><small>{{ $day['count'] }} جلسه</small></div><div class="teacher-week-events">
@forelse($day['sessions'] as $session)<div class="teacher-week-event"><strong>{{ $session->title ?: $session->classroom?->name }}</strong><small>{{ $session->starts_at?->format('H:i') }} · {{ $session->classroom?->course?->title ?? $session->classroom?->name }}</small></div>
@empty<span class="teacher-no-event">بدون جلسه</span>@endforelse
</div></div>
@endforeach
</div></section>
<div class="g g2">
<section class="c"><div class="teacher-section-head"><h2>کلاس‌های فعال شما</h2><a href="{{ route('teacher.classes') }}">همه کلاس‌ها</a></div>
@forelse(($todayClasses ?? []) as $cl)<div class="row"><div>{{ data_get($cl,'title') }}<small>{{ data_get($cl,'time') }}</small></div>@if(data_get($cl,'status')==='link')<a class="b s" href="{{ data_get($cl,'url') }}" target="_blank" rel="noopener noreferrer">ورود</a>@else<i class="bd am">لینک جلسه ثبت نشده</i>@endif</div>
@empty<p>کلاس فعالی برای حساب شما ثبت نشده است.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>جلسات امروز</h2><a href="{{ route('teacher.live') }}">کلاس آنلاین</a></div>
@forelse(($todaySessions ?? []) as $session)<div class="row"><div>{{ data_get($session,'title') }}<small>{{ data_get($session,'time') }}</small></div>@if(data_get($session,'status')==='link')<a class="b s" href="{{ data_get($session,'url') }}" target="_blank" rel="noopener noreferrer">ورود به کلاس</a>@elseif(data_get($session,'status')==='waiting_jitsi')<i class="bd am">لینک Jitsi ثبت نشده است</i>@elseif(data_get($session,'status')==='waiting')<i class="bd am">در انتظار فعال‌سازی Skyroom</i>@else<i class="bd bl">جلسه حضوری</i>@endif</div>
@empty<p>برای امروز جلسه‌ای زمان‌بندی نشده است.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>پاسخ‌های منتظر بررسی</h2><a href="{{ route('teacher.homework') }}">مدیریت تکالیف</a></div>
@forelse(($pendingHomework ?? []) as $item)<div class="row"><div>{{ data_get($item,'title') }}<small>{{ data_get($item,'submitted',0) }} پاسخ دانش‌آموز</small></div><i class="bd bl">در انتظار بررسی</i></div>
@empty<p>پاسخ بررسی‌نشده‌ای وجود ندارد.</p>@endforelse</section>
<section class="c"><div class="teacher-section-head"><h2>پیام‌های تازه</h2><a href="{{ route('teacher.messages.index') }}">صندوق پیام</a></div>
@forelse(($newMessages ?? []) as $message)<div class="row"><div>{{ data_get($message,'from') }}<small>{{ data_get($message,'text') }}</small></div><i class="bd pk">باز</i></div>
@empty<p>پیام بازی وجود ندارد.</p>@endforelse</section>
</div>
@endsection