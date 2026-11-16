@extends('parent.layouts.app')

@section('title', 'داشبورد')

@section('content')
@php
    $att   = $attendance ?? [];
    $parts = [
        ['l' => 'حاضر', 'v' => (int) ($att['present'] ?? 0), 'c' => '--gn'],
        ['l' => 'غایب', 'v' => (int) ($att['absent'] ?? 0),  'c' => '--rd'],
        ['l' => 'تأخیر', 'v' => (int) ($att['late'] ?? 0),   'c' => '--am'],
    ];
    $subj = collect($subjects ?? []);
@endphp
<h2>گزارش {{ data_get($kid ?? null, 'name') }}</h2>
@include('parent.partials.kid-picker')
<div class="g">
    <div class="g g4">
        <div class="st"><b>{{ $stats['avg'] ?? '' }}</b><span>میانگین نمرات</span><small>{{ $stats['avg_note'] ?? '' }}</small></div>
        <div class="st gn"><b>{{ $att['percent'] ?? 0 }}٪</b><span>حضور</span><small>{{ $att['absent'] ?? 0 }} غیبت · {{ $att['late'] ?? 0 }} تأخیر</small></div>
        <div class="st bl"><b>{{ $stats['open_homework'] ?? 0 }}</b><span>تکلیف باز</span><small>{{ $stats['open_homework_note'] ?? '' }}</small></div>
        <div class="st am"><b>{{ $stats['next_meeting'] ?? '' }}</b><span>جلسه بعدی</span><small>{{ $stats['next_meeting_note'] ?? '' }}</small></div>
    </div>

    <div class="g g2">
        <section class="c"><h3>روند نمرات در برابر میانگین کلاس</h3>
            @include('parent.partials.chart', [
                'type' => 'line', 'labels' => $months ?? [],
                'series' => [
                    ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $scores ?? [], 'area' => true],
                    ['name' => 'میانگین کلاس', 'c' => '--mu', 'data' => $classAvg ?? [], 'dash' => true],
                ],
            ])
        </section>

        <section class="c"><h3>وضعیت حضور این ماه</h3>
            @include('parent.partials.chart', ['type' => 'donut', 'parts' => $parts, 'big' => ($att['percent'] ?? 0).'٪', 'small' => 'حضور'])
        </section>

        <section class="c"><h3>مقایسه درس‌ها با میانگین کلاس</h3>
            @include('parent.partials.chart', [
                'type' => 'radar',
                'labels' => $subj->map(fn ($s) => data_get($s, 'name'))->values()->all(),
                'series' => [
                    ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $subj->map(fn ($s) => data_get($s, 'score'))->values()->all()],
                    ['name' => 'میانگین کلاس', 'c' => '--am', 'data' => $subj->map(fn ($s) => data_get($s, 'class_avg'))->values()->all()],
                ],
            ])
        </section>

        <section class="c"><h3>ارسال تکالیف در شش ماه</h3>
            @include('parent.partials.chart', [
                'type' => 'bar', 'labels' => $months ?? [], 'options' => ['stacked' => true, 'max' => 8],
                'series' => [
                    ['name' => 'به‌موقع', 'c' => '--gn', 'data' => $hwOnTime ?? []],
                    ['name' => 'با تأخیر', 'c' => '--am', 'data' => $hwLate ?? []],
                    ['name' => 'ارسال‌نشده', 'c' => '--rd', 'data' => $hwMissing ?? []],
                ],
            ])
        </section>

        <section class="c"><h3>آزمون‌های پیش‌رو</h3>
            @forelse (($upcomingExams ?? []) as $e)
                <div class="row"><div>{{ data_get($e, 'title') }}<small>{{ data_get($e, 'date') }}</small></div><i class="bd pk">آزمون</i></div>
            @empty
                <p>آزمونی در پیش نیست.</p>
            @endforelse
        </section>

        <section class="c"><h3>نکته‌های مدرس</h3>
            @forelse (($teacherNotes ?? []) as $n)
                <div class="row"><div>{{ data_get($n, 'teacher') }}<small>{{ data_get($n, 'text') }}</small></div>
                    @if (data_get($n, 'type') === 'praise')<i class="bd gn">تشویق</i>@else<i class="bd am">پیشنهاد</i>@endif
                </div>
            @empty
                <p>نکته‌ای ثبت نشده است.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
