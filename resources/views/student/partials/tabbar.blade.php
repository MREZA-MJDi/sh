@php
    $dashboardAnchor = static fn (string $section): string => request()->routeIs('student.dashboard')
        ? '#' . $section
        : route('student.dashboard') . '#' . $section;
@endphp

<nav class="student-mobile-nav" aria-label="دسترسی سریع دانش‌آموز">
    <a href="{{ route('student.dashboard') }}" @class(['on' => request()->routeIs('student.dashboard')]) aria-label="خانه">
        <x-icon name="home"/>
        <span>خانه</span>
    </a>
    @can('classes.view.enrolled')
        <a href="{{ $dashboardAnchor('classes') }}" data-dashboard-nav-target="classes">
            <x-icon name="book"/>
            <span>کلاس‌ها</span>
        </a>
    @endcan
    @can('homework.view.own')
        <a href="{{ $dashboardAnchor('homework') }}" data-dashboard-nav-target="homework">
            <x-icon name="task"/>
            <span>تکالیف</span>
        </a>
    @endcan
    @can('exams.view.own')
        <a href="{{ $dashboardAnchor('results') }}" data-dashboard-nav-target="results">
            <x-icon name="exam"/>
            <span>نتایج</span>
        </a>
    @endcan
    @can('messages.view.own')
        <a href="{{ route('student.messages.index') }}" @class(['on' => request()->routeIs('student.messages.*')])>
            <x-icon name="msg"/>
            <span>پیام‌ها</span>
        </a>
    @endcan
</nav>
