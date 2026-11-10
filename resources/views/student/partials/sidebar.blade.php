@php
    $student = auth()->user();
    $dashboardAnchor = static fn (string $section): string => request()->routeIs('student.dashboard')
        ? '#' . $section
        : route('student.dashboard') . '#' . $section;
    $hasLearningItems = $student?->can('classes.view.enrolled') || $student?->can('homework.view.own') || $student?->can('lessons.view');
    $hasPerformanceItems = $student?->can('exams.view.own') || $student?->can('attendance.view.own');
@endphp

<aside class="side" id="side" aria-label="منوی دانش‌آموز">
    <div class="me">
        <div class="av">
            <svg viewBox="0 0 120 120" width="100%" height="100%" aria-hidden="true">
                <use href="#avatar"></use>
            </svg>
        </div>
        <h3>{{ $student?->name ?? 'دانش‌آموز' }}</h3>
        <small>کارتابل دانش‌آموز</small>
    </div>

    <nav class="nav" aria-label="بخش‌های کارتابل دانش‌آموز">
        <a href="{{ $dashboardAnchor('overview') }}" class="on" aria-current="page" data-nav-link data-dashboard-nav-target="overview">
            <x-icon name="home"/> خانه‌ی یادگیری
        </a>

        @if($hasLearningItems)
            <h6>یادگیری</h6>
            @can('lessons.view')
                <a href="{{ route('student.courses.index') }}" data-nav-link><x-icon name="book"/> دوره‌های من</a>
            @endcan
            @can('classes.view.enrolled')
                <a href="{{ $dashboardAnchor('classes') }}" data-nav-link data-dashboard-nav-target="classes"><x-icon name="book"/> کلاس‌های من</a>
            @endcan
            @can('homework.view.own')
                <a href="{{ $dashboardAnchor('homework') }}" data-nav-link data-dashboard-nav-target="homework"><x-icon name="task"/> تکالیف</a>
            @endcan
        @endif

        @if($hasPerformanceItems)
            <h6>عملکرد آموزشی</h6>
            @can('exams.view.own')
                <a href="{{ route('student.exams.index') }}" data-nav-link><x-icon name="exam"/> آزمون‌های من</a>
                <a href="{{ $dashboardAnchor('results') }}" data-nav-link data-dashboard-nav-target="results"><x-icon name="chart"/> نتایج آزمون‌ها</a>
            @endcan
            @can('attendance.view.own')
                <a href="{{ $dashboardAnchor('attendance') }}" data-nav-link data-dashboard-nav-target="attendance"><x-icon name="cal"/> حضور و غیاب</a>
            @endcan
        @endif

        @can('resources.view.enrolled')
            <h6>منابع</h6>
            <a href="{{ $dashboardAnchor('resources') }}" data-nav-link data-dashboard-nav-target="resources"><x-icon name="file"/> جزوه‌ها و منابع</a>
        @endcan

        @can('notes.manage.own')
            <h6>ابزارهای شخصی</h6>
            <a href="{{ $dashboardAnchor('notes') }}" data-nav-link data-dashboard-nav-target="notes"><x-icon name="note"/> یادداشت‌های من</a>
        @endcan

        @can('messages.view.own')
            <h6>ارتباط</h6>
            <a href="{{ route('student.messages.index') }}"><x-icon name="msg"/> پیام به مدرس‌ها</a>
        @endcan

        @can('announcements.view')
            <h6>اطلاع‌رسانی</h6>
            <a href="{{ $dashboardAnchor('announcements') }}" data-nav-link data-dashboard-nav-target="announcements"><x-icon name="bell"/> اطلاعیه‌های آکادمی</a>
        @endcan
    </nav>

    <div class="prem">
        <h4>مسیر یادگیری</h4>
        <p>کلاس‌ها، تکالیف و نتایج آموزشی خودت را از همین‌جا پیگیری کن.</p>
        @can('classes.view.enrolled')
            <a class="btn s" href="{{ $dashboardAnchor('classes') }}">مشاهده‌ی کلاس‌ها</a>
        @endcan
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">خروج از حساب</button>
        </form>
    </div>
</aside>
