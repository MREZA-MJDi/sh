@extends('teacher.layouts.app')

@section('title', 'حضور و غیاب')

@section('content')
@php
    $statuses = collect($students ?? [])->map(fn ($s) => (int) data_get($s, 'status', 0));
    $label = ['حاضر', 'غایب', 'تأخیر'];
    $cls   = ['gn', 'rd', 'am'];
@endphp
<h2>حضور و غیاب — {{ data_get($class ?? null, 'title') }}</h2>
<div class="g g2">
    <section class="c"><h3>{{ $monthTitle ?? 'تقویم' }}</h3>
        <div class="cal2">
            @foreach (['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as $w)<small class="wk">{{ $w }}</small>@endforeach
            @for ($i = 0; $i < ($monthOffset ?? 0); $i++)<i></i>@endfor
            @foreach (($calendar ?? []) as $d)
                @php $n = data_get($d, 'day'); $state = data_get($d, 'state', 'open'); $a = (int) data_get($d, 'absent', 0); $l = (int) data_get($d, 'late', 0); @endphp
                @if ($state === 'open')
                    <a class="dc {{ ($selectedDay ?? null) == $n ? 'on' : '' }}" href="?day={{ $n }}"><b>{{ $n }}</b>
                        @if ($a)<small style="color:var(--rd)">{{ $a }} غایب</small>@endif
                        @if ($l)<small style="color:var(--am)">{{ $l }} تأخیر</small>@endif
                        @if (!$a && !$l)<small style="color:var(--gn)">کامل ✓</small>@endif
                    </a>
                @else
                    <span class="dc o"><b>{{ $n }}</b>@if ($state === 'holiday')<small>تعطیل</small>@endif</span>
                @endif
            @endforeach
        </div>
        <p style="color:var(--mu)">روی هر روز ضربه بزنید تا جزئیات و غیبت‌ها را ببینید.</p>
    </section>

    <section class="c"><h3>جزئیات {{ $selectedDay ?? '' }} {{ $monthName ?? '' }}</h3>
        @forelse (($dayDetails ?? []) as $r)
            @php $absent = data_get($r, 'type') === 'absent'; $just = (bool) data_get($r, 'justified', false); @endphp
            <div class="row"><div>{{ data_get($r, 'name') }}<small>{{ $absent ? ($just ? 'غیبت موجه' : 'غیبت غیرموجه') : data_get($r, 'late_minutes', 0).' دقیقه تأخیر' }}</small></div>
                <i class="bd {{ $absent ? 'rd' : 'am' }}">{{ $absent ? 'غایب' : 'تأخیر' }}</i>
                @if ($absent)
                    <form method="POST" action="{{ url('teacher/attendance/'.data_get($r, 'id').'/justify') }}" class="inline">@csrf
                        <input type="hidden" name="justified" value="{{ $just ? 0 : 1 }}">
                        <button class="b s" type="submit">{{ $just ? 'لغو موجه' : 'ثبت موجه' }}</button>
                    </form>
                @endif
            </div>
        @empty
            <p>همه حاضر بودند ✅</p>
        @endforelse
    </section>
</div>

<div style="height:14px"></div>
<div class="g g2">
    <section class="c"><h3>خلاصه حضور ماه</h3>
        @forelse (($monthSummary ?? []) as $s)
            @php $warn = (int) data_get($s, 'absent', 0) > 3; $p = (int) data_get($s, 'percent', 0); @endphp
            <div class="row"><div>{{ data_get($s, 'name') }}<small>{{ data_get($s, 'absent', 0) }} غیبت · {{ data_get($s, 'late', 0) }} تأخیر</small></div>
                <div class="pr"><u class="{{ $warn ? 'am' : 'gn' }}" style="width:{{ $p }}%"></u></div>
                <i class="bd {{ $warn ? 'am' : 'gn' }}">{{ $p }}٪</i>
            </div>
        @empty
            <p>داده‌ای نیست.</p>
        @endforelse
    </section>
</div>

<h3 style="margin:18px 0 10px">حضور و غیاب امروز</h3>
<form method="POST" action="{{ url('teacher/attendance/roll-call') }}" data-rollcall>
    @csrf
    <div class="g g4" style="margin-bottom:14px">
        <div class="st gn"><b data-count="0">{{ $statuses->filter(fn ($x) => $x === 0)->count() }}</b><span>حاضر</span><small></small></div>
        <div class="st pk"><b data-count="1">{{ $statuses->filter(fn ($x) => $x === 1)->count() }}</b><span>غایب</span><small></small></div>
        <div class="st am"><b data-count="2">{{ $statuses->filter(fn ($x) => $x === 2)->count() }}</b><span>تأخیر</span><small></small></div>
    </div>
    <section class="c"><h3>برای تغییر وضعیت روی نام ضربه بزنید</h3>
        @forelse (($students ?? []) as $s)
            @php $st = (int) data_get($s, 'status', 0); @endphp
            <div class="row"><div>{{ data_get($s, 'name') }}<small></small></div>
                <input type="hidden" name="status[{{ data_get($s, 'id') }}]" value="{{ $st }}">
                <button type="button" class="st-btn bd {{ $cls[$st] }}" data-status>{{ $label[$st] }}</button>
            </div>
        @empty
            <p>دانش‌آموزی ثبت نشده است.</p>
        @endforelse
        <br><button class="b" type="submit">ذخیره</button>
    </section>
</form>
@endsection
