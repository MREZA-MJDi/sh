@extends('student.layouts.app')
@section('title', 'مرکز یادگیری')
@section('hide-topbar-title', '1')

@section('content')
@php
    $nextSession = $next_session ?? null;
    $sections = [
        'برنامه آموزشی' => [
            ['student.live-classes', 'برنامه کلاس‌ها', 'calendar', null, $upcoming_session_count, $nextSession?->starts_at ? \App\Support\PersianDate::format($nextSession->starts_at, 'Y/m/d H:i') : null],
            ['student.homework.index', 'تکالیف من', 'check', 'homework.submit', $pending_homework_count, 'در انتظار ارسال'],
            ['student.courses.index', 'دوره‌های من', 'book', 'lessons.view', null, number_format($classroom_count).' کلاس فعال'],
            ['student.exams.index', 'آزمون‌ها و کارنامه', 'chart', 'exams.view.own', $result_count, 'نتایج ثبت‌شده'],
            ['student.attendance', 'حضور و غیاب', 'check', 'attendance.view.own', null, null],
        ],
        'محتوای آموزشی' => [
            ['student.videos.index', 'ویدیوهای ضبط‌شده', 'video', 'videos.view.enrolled', null, null],
            ['student.resources.index', 'جزوه‌ها و منابع', 'file', 'resources.view.enrolled', null, null],
            ['student.library', 'کتابخانه و خریدها', 'wallet', null, null, null],
        ],
        'ارتباط با آموزشگاه' => [
            ['student.messages.index', 'پیام‌ها و ارتباط با مدرس', 'message', 'messages.view.own', null, null],
            ['student.meetings.index', 'جلسات با اولیا', 'calendar', null, null, null],
        ],
    ];
@endphp

<section class="page" id="overview">
    <x-portal.page-head title="مرکز یادگیری" icon="home">
        <x-slot:subtitle>سلام {{ $student->name }}! برای شروع، یکی از کارهای زیر را انتخاب کن.</x-slot:subtitle>
        @if($student->hasPermissionTo('lessons.view'))
            <a class="btn s" href="{{ route('student.courses.index') }}">ادامه یادگیری</a>
        @endif
    </x-portal.page-head>

    @include('components.announcement-feed', ['announcements' => $announcements ?? collect()])

    @foreach($sections as $heading => $items)
        <section class="card">
            <div class="chead"><h2>{{ $heading }}</h2></div>
            @foreach($items as [$routeName, $label, $icon, $permission, $count, $hint])
                @continue($permission && !$student->hasPermissionTo($permission))
                <a class="trk" href="{{ route($routeName) }}">
                    <span class="portal-state-icon" aria-hidden="true"><x-portal.icon :name="$icon"/></span>
                    <div>
                        <b>{{ $label }}</b>
                        @if($hint)<span>{{ $hint }}</span>@endif
                    </div>
                    @if($count !== null)<span class="tag">{{ number_format($count) }}</span>@endif
                    <x-portal.icon name="back" aria-hidden="true"/>
                </a>
            @endforeach
        </section>
    @endforeach
</section>
@endsection
