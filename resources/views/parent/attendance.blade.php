@extends('parent.layouts.app')

@section('title', 'حضور و غیاب')

@section('content')
@php
    $att   = $attendance ?? [];
    $parts = [
        ['l' => 'حاضر', 'v' => (int) ($att['present'] ?? 0), 'c' => '--gn'],
        ['l' => 'غایب', 'v' => (int) ($att['absent'] ?? 0),  'c' => '--rd'],
        ['l' => 'تأخیر', 'v' => (int) ($att['late'] ?? 0),   'c' => '--am'],
    ];
@endphp
<h2>حضور و غیاب {{ data_get($kid ?? null, 'name') }}</h2>
@include('parent.partials.kid-picker')
<div class="g g2">
    <section class="c"><h3>{{ $monthTitle ?? 'تقویم' }}</h3>
        <div class="cal2 sh-calendar-grid">
            @foreach (['ش', 'ی', 'د', 'س', 'چ', 'پ', 'ج'] as $w)<small class="wk">{{ $w }}</small>@endforeach
            @for ($i = 0; $i < ($monthOffset ?? 0); $i++)<i></i>@endfor
            {{-- status: p حاضر | a غایب | l تأخیر | off تعطیل | future --}}
            @foreach (($calendar ?? []) as $d)
                <div class="dc {{ data_get($d, 'status', 'future') }}{{ data_get($d, 'today') ? ' today' : '' }}"><b>{{ data_get($d, 'day') }}</b></div>
            @endforeach
        </div>
        <div class="leg">
            <span><i style="background:var(--gn)"></i>حاضر</span>
            <span><i style="background:var(--rd)"></i>غایب</span>
            <span><i style="background:var(--am)"></i>تأخیر</span>
            <span><i style="background:var(--mu)"></i>تعطیل</span>
        </div>
    </section>

    <section class="c"><h3>خلاصه ماه</h3>
        @include('parent.partials.chart', ['type' => 'donut', 'parts' => $parts, 'big' => ($att['percent'] ?? 0).'٪', 'small' => 'حضور'])
        <hr class="hr">
        <div class="row"><div>بیشترین غیبت<small>{{ $absencePattern ?? '' }}</small></div><i class="bd bl">الگو</i></div>
        <div class="row"><div>آخرین تأخیر<small>{{ $lastLate ?? '' }}</small></div><i class="bd am">تأخیر</i></div>
    </section>
</div>
@endsection
