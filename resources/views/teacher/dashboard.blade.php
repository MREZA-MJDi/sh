@extends('teacher.layouts.app')

@section('title', 'داشبورد')

@section('content')
<h2>سلام {{ data_get($teacher ?? auth()->user(), 'name') }} 👋</h2>
<div class="g">
    @if (!empty($liveClass))
    <div class="live">
        <div>
            <h3><span class="pulse"></span>{{ data_get($liveClass, 'title') }} — الان زنده است</h3>
            <p>{{ data_get($liveClass, 'time') }} · {{ data_get($liveClass, 'online_count', 0) }} دانش‌آموز آنلاین</p>
        </div>
        <a class="b" href="{{ data_get($liveClass, 'join_url', url('teacher/live/join')) }}" target="_blank" rel="noopener">ورود به کلاس</a>
    </div>
    @endif

    <div class="g g4">
        <div class="st am"><b>{{ $stats['classes_today'] ?? 0 }}</b><span>کلاس امروز</span><small>{{ $stats['classes_today_note'] ?? '' }}</small></div>
        <div class="st bl"><b>{{ $stats['homework_review'] ?? 0 }}</b><span>تکلیف برای بررسی</span><small>{{ $stats['homework_review_note'] ?? '' }}</small></div>
        <div class="st pk"><b>{{ $stats['absent_today'] ?? 0 }}</b><span>غایب امروز</span><small>{{ $stats['absent_today_note'] ?? '' }}</small></div>
        <div class="st"><b>{{ $stats['new_messages'] ?? 0 }}</b><span>پیام جدید اولیا</span><small>{{ $stats['new_messages_note'] ?? '' }}</small></div>
    </div>

    <div class="g g2">
        <section class="c"><h3>برنامه امروز</h3>
            @forelse (($todayClasses ?? []) as $cl)
                <div class="row"><div>{{ data_get($cl, 'title') }}<small>{{ data_get($cl, 'time') }}</small></div>
                    @php $st = data_get($cl, 'status'); @endphp
                    @if ($st === 'live')<i class="bd gn">زنده</i>
                    @elseif ($st === 'link')<i class="bd am">لینک تنظیم شده</i>
                    @else<button type="button" class="b s" data-modal="links" data-action="{{ url('teacher/links') }}" data-method="POST" data-values="{{ json_encode(['title' => data_get($cl, 'title')], JSON_UNESCAPED_UNICODE) }}">افزودن لینک</button>
                    @endif
                </div>
            @empty
                <p>کلاسی برای امروز ثبت نشده است.</p>
            @endforelse
        </section>

        <section class="c"><h3>حضور این هفته</h3>
            <div class="bars">
                @foreach (($weekAttendance ?? []) as $d)
                    <div><i style="height:{{ (int) data_get($d, 'percent', 0) }}%"></i>{{ data_get($d, 'label') }}</div>
                @endforeach
            </div>
        </section>

        <section class="c"><h3>تکالیف در انتظار بررسی</h3>
            @forelse (($pendingHomework ?? []) as $h)
                <div class="row"><div>{{ data_get($h, 'title') }}<small>{{ data_get($h, 'submitted', 0) }} از {{ data_get($h, 'total', 0) }} ارسال</small></div><i class="bd bl">{{ data_get($h, 'type') }}</i></div>
            @empty
                <p>موردی نیست.</p>
            @endforelse
        </section>

        <section class="c"><h3>پیام‌های تازه</h3>
            @forelse (($newMessages ?? []) as $m)
                <div class="row"><div>{{ data_get($m, 'from') }}<small>{{ data_get($m, 'text') }}</small></div><i class="bd pk">جدید</i></div>
            @empty
                <p>پیام جدیدی نیست.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
