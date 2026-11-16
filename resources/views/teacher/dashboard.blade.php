@extends('teacher.layouts.app')

@section('title', 'داشبورد')

@section('content')
<h2>سلام {{ data_get($teacher ?? auth()->user(), 'name') }} 👋</h2>
<div class="g">
    <div class="g g4">
        <div class="st am"><b>{{ $stats['classes_today'] }}</b><span>کلاس‌های تحت مدیریت</span><small>{{ $stats['classes_today_note'] }}</small></div>
        <div class="st bl"><b>{{ $stats['homework_review'] }}</b><span>تکلیف برای بررسی</span><small>{{ $stats['homework_review_note'] }}</small></div>
        <div class="st pk"><b>{{ $stats['absent_today'] }}</b><span>غایب امروز</span><small>{{ $stats['absent_today_note'] }}</small></div>
        <div class="st"><b>{{ $stats['new_messages'] }}</b><span>پیام‌های باز</span><small>{{ $stats['new_messages_note'] }}</small></div>
    </div>
    <div class="g g2">
        <section class="c"><h3>کلاس‌های تحت مدیریت</h3>
            @forelse (($todayClasses ?? []) as $cl)
                <div class="row"><div>{{ data_get($cl, 'title') }}<small>{{ data_get($cl, 'time') }}</small></div>
                    @if (data_get($cl, 'status') === 'link')<i class="bd gn">لینک کلاس ثبت شده</i>@else<i class="bd am">بدون لینک جلسه</i>@endif
                </div>
            @empty
                <p>کلاس فعالی برای حساب شما ثبت نشده است.</p>
            @endforelse
        </section>
        <section class="c"><h3>نرخ حضور این هفته</h3>
            <div class="bars">
                @forelse (($weekAttendance ?? []) as $d)
                    <div><i style="height:{{ (int) data_get($d, 'percent', 0) }}%"></i>{{ data_get($d, 'label') }}<small>{{ data_get($d, 'percent', 0) }}٪</small></div>
                @empty
                    <p>داده‌ی حضور و غیاب ثبت نشده است.</p>
                @endforelse
            </div>
        </section>
        <section class="c"><h3>تکالیف در انتظار بررسی</h3>
            @forelse (($pendingHomework ?? []) as $h)
                <div class="row"><div>{{ data_get($h, 'title') }}<small>{{ data_get($h, 'submitted', 0) }} ارسال منتظر بررسی</small></div><i class="bd bl">{{ data_get($h, 'type') }}</i></div>
            @empty
                <p>تکلیف بررسی‌نشده‌ای وجود ندارد.</p>
            @endforelse
        </section>
        <section class="c"><h3>پیام‌های تازه</h3>
            @forelse (($newMessages ?? []) as $m)
                <div class="row"><div>{{ data_get($m, 'from') }}<small>{{ data_get($m, 'text') }}</small></div><i class="bd pk">باز</i></div>
            @empty
                <p>پیام بازی وجود ندارد.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
