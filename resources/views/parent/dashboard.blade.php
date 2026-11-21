@extends('parent.layouts.app')

@section('title', 'داشبورد')

@section('content')
@php
    $att = $attendance ?? [];
    $parts = [
        ['l' => 'حاضر', 'v' => (int) ($att['present'] ?? 0), 'c' => '--gn'],
        ['l' => 'غایب', 'v' => (int) ($att['absent'] ?? 0), 'c' => '--rd'],
        ['l' => 'تأخیر', 'v' => (int) ($att['late'] ?? 0), 'c' => '--am'],
    ];
    $subj = collect($subjects ?? []);
@endphp
<div class="parent-page-heading"><div><span class="parent-eyebrow">داشبورد خانواده</span><h1>وضعیت آموزشی {{ data_get($kid ?? null, 'name') }}</h1><p>خلاصه‌ای از پیشرفت، حضور و نتایج آموزشی فرزند شما</p></div></div>
@include('parent.partials.kid-picker')
<div class="parent-dashboard">
    <div class="g g4 parent-dashboard-stats">
        <div class="st"><b>{{ $stats['avg'] ?? '—' }}</b><span>میانگین نمرات ثبت‌شده</span><small>{{ $stats['avg_note'] ?? '' }}</small></div>
        <div class="st gn"><b>{{ $att['percent'] ?? 0 }}٪</b><span>حضور</span><small>{{ $att['absent'] ?? 0 }} غیبت · {{ $att['late'] ?? 0 }} تأخیر</small></div>
        <div class="st bl"><b>{{ $stats['open_homework'] ?? 0 }}</b><span>تکلیف بدون ارسال</span><small>{{ $stats['open_homework_note'] ?? '' }}</small></div>
        <div class="st am"><b>{{ $stats['next_meeting'] ?? '—' }}</b><span>جلسه بعدی</span><small>{{ $stats['next_meeting_note'] ?? '' }}</small></div>
    </div>
    <div class="g g2 parent-dashboard-grid">

    <section class="c parent-dashboard-card">
        <h3>پیشرفت دوره‌ها و درس‌ها</h3>
        @forelse (($course_progress ?? collect()) as $course)
            <div class="row">
                <div>{{ data_get($course, 'title') }}
                    <small>{{ strtr((string) data_get($course, 'completed_lessons', 0), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} از {{ strtr((string) data_get($course, 'total_lessons', 0), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} درس تکمیل‌شده</small>
                </div>
                <div class="pr"><u style="width:{{ (int) data_get($course, 'progress_percent', 0) }}%"></u></div>
            </div>
        @empty
            <p>پیشرفت دوره‌ای برای نمایش وجود ندارد.</p>
        @endforelse
    </section>
        <section class="c parent-dashboard-card"><h3>روند نمرات ثبت‌شده</h3>
            @include('parent.partials.chart', ['type' => 'line', 'labels' => $months ?? [], 'series' => [
                ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $scores ?? [], 'area' => true],
            ]])
        </section>
        <section class="c parent-dashboard-card"><h3>وضعیت حضور ثبت‌شده</h3>
            @include('parent.partials.chart', ['type' => 'donut', 'parts' => $parts, 'big' => ($att['percent'] ?? 0).'٪', 'small' => 'حضور'])
        </section>
        <section class="c parent-dashboard-card"><h3>عملکرد درس‌ها</h3>
            @include('parent.partials.chart', ['type' => 'radar', 'labels' => $subj->pluck('name')->all(), 'series' => [
                ['name' => data_get($kid ?? null, 'name', ''), 'c' => '--pri', 'data' => $subj->pluck('score')->all()],
            ]])
        </section>
        <section class="c parent-dashboard-card"><h3>وضعیت ارسال تکالیف</h3>
            @include('parent.partials.chart', ['type' => 'bar', 'labels' => $homeworkMonths ?? [], 'options' => ['stacked' => true], 'series' => [
                ['name' => 'به‌موقع', 'c' => '--gn', 'data' => $hwOnTime ?? []],
                ['name' => 'با تأخیر', 'c' => '--am', 'data' => $hwLate ?? []],
                ['name' => 'ارسال‌نشده', 'c' => '--rd', 'data' => $hwMissing ?? []],
            ]])
        </section>
        <section class="c parent-dashboard-card"><h3>نتایج آزمون‌های قابل مشاهده</h3>
            @forelse (($results ?? collect()) as $result)
                <div class="row"><div>{{ $result->exam?->title }}<small>{{ $result->created_at?->format('Y/m/d') }}</small>@if($result->feedback)<small>{{ $result->feedback }}</small>@endif</div><i class="bd pk">{{ $result->score }} / {{ $result->max_score }}</i></div>
            @empty
                <p>نتیجه‌ی قابل نمایشی ثبت نشده است.</p>
            @endforelse
        </section>
        <section class="c parent-dashboard-card"><h3>نتایج بررسی تکالیف و بازخورد مدرس</h3>
            @forelse (($teacherNotes ?? collect()) as $note)
                <div class="row"><div>{{ data_get($note, 'homework') }} · {{ data_get($note, 'teacher') }}<small>نتیجه بررسی مدرس: {{ data_get($note, 'text') }}</small>@if (data_get($note, 'score') !== null)<small>نمره: {{ strtr(rtrim(rtrim(number_format((float) data_get($note, 'score'), 2, '.', ''), '0'), '.'), ['0'=>'۰','1'=>'۱','2'=>'۲','3'=>'۳','4'=>'۴','5'=>'۵','6'=>'۶','7'=>'۷','8'=>'۸','9'=>'۹']) }} از ۲۰</small>@endif</div><i class="bd am">بازخورد</i></div>
            @empty
                <p>بازخوردی ثبت نشده است.</p>
            @endforelse
        </section>
    </div>
</div>
@endsection
