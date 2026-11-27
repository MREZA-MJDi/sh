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
<div class="parent-page-heading parent-dashboard-hero">
    <div class="parent-hero-identity">
        <div class="parent-hero-avatar">
            @if(($kid ?? null)?->avatarUrl())
                <img src="{{ $kid->avatarUrl() }}" alt="{{ data_get($kid, 'name') }}">
            @else
                <span class="avatar-letter">{{ mb_substr((string) data_get($kid, 'name', 'ف'), 0, 1) }}</span>
            @endif
        </div>
        <div class="parent-hero-copy">
            <div class="parent-hero-badges">
                <span class="parent-eyebrow">
                    <svg viewBox="0 0 24 24" width="12" height="12" stroke="currentColor" stroke-width="2.2" fill="none"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
                    داشبورد خانواده
                </span>
                @if($kid)
                    <span class="parent-active-child-tag">دانش‌آموز: {{ data_get($kid, 'name') }}</span>
                @endif
            </div>
            <h1>وضعیت آموزشی {{ data_get($kid ?? null, 'name') }}</h1>
            <p>خلاصه‌ای از پیشرفت، حضور و نتایج آموزشی فرزند شما</p>
        </div>
    </div>
</div>
@include('parent.partials.kid-picker')
<div class="parent-dashboard">
    @include('components.announcement-feed', ['announcements' => $announcements ?? collect()])
    <div class="portal-action-grid">
        @if(auth()->user()->hasPermissionTo('attendance.view.children'))<x-portal.action-card :href="route('guardian.attendance', ['child'=>$kid?->id])" title="گزارش حضور" icon="check" tone="tracking"/>@endif
        @if(auth()->user()->hasPermissionTo('homework.view.children'))<x-portal.action-card :href="route('guardian.homework', ['child'=>$kid?->id])" title="تکالیف فرزند" icon="file" tone="tracking"/>@endif
        @if(auth()->user()->hasPermissionTo('exams.view.children'))<x-portal.action-card :href="route('guardian.exams', ['child'=>$kid?->id])" title="کارنامه" icon="chart" tone="tracking"/>@endif
        <x-portal.action-card :href="route('guardian.meetings.index', ['child'=>$kid?->id])" title="جلسات خانواده" icon="calendar" tone="communication"/>
    </div>
    <div class="g g4 parent-dashboard-stats">
        <div class="st"><b>{{ $stats['avg'] ?? '—' }}</b><span>میانگین نمرات ثبت‌شده</span><small>{{ $stats['avg_note'] ?? '' }}</small></div>
        <div class="st gn"><b>{{ isset($att['percent']) ? $att['percent'].'٪' : '—' }}</b><span>حضور</span><small>{{ $att['absent'] ?? '—' }} غیبت · {{ $att['late'] ?? '—' }} تأخیر</small></div>
        <div class="st bl"><b>{{ $stats['open_homework'] ?? '—' }}</b><span>تکلیف بدون ارسال</span><small>{{ $stats['open_homework_note'] ?? '' }}</small></div>
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
            @include('parent.partials.chart', ['type' => 'donut', 'parts' => $parts, 'big' => isset($att['percent']) ? $att['percent'].'٪' : '—', 'small' => 'حضور'])
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
                <div class="row"><div>{{ $result->exam?->title }}<small>{{ data_get($resultDates ?? [], $result->id, '—') }}</small>@if($result->feedback)<small>{{ $result->feedback }}</small>@endif</div><i class="bd pk">{{ $result->score }} / {{ $result->max_score }}</i></div>
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
