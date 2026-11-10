@php
    $student = auth()->user();
    $hasLearningItems = $student?->can('classes.view.enrolled') || $student?->can('homework.view.own');
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
        <a href="#overview" class="on" aria-current="page" data-nav-link>
            <x-icon name="home"/> خانه‌ی یادگیری
        </a>

        @if($hasLearningItems)
            <h6>یادگیری</h6>
            @can('classes.view.enrolled')
                <a href="#classes" data-nav-link><x-icon name="book"/> کلاس‌های من</a>
            @endcan
            @can('homework.view.own')
                <a href="#homework" data-nav-link><x-icon name="task"/> تکالیف</a>
            @endcan
        @endif

        @if($hasPerformanceItems)
            <h6>عملکرد آموزشی</h6>
            @can('exams.view.own')
                <a href="#results" data-nav-link><x-icon name="exam"/> نتایج آزمون‌ها</a>
            @endcan
            @can('attendance.view.own')
                <a href="#attendance" data-nav-link><x-icon name="cal"/> حضور و غیاب</a>
            @endcan
        @endif

        @can('resources.view.enrolled')
            <h6>منابع</h6>
            <a href="#resources" data-nav-link><x-icon name="file"/> جزوه‌ها و منابع</a>
        @endcan

        @can('announcements.view')
            <h6>ارتباط</h6>
            <a href="#announcements" data-nav-link><x-icon name="bell"/> اطلاعیه‌های آکادمی</a>
        @endcan
    </nav>

    <div class="prem">
        <h4>مسیر یادگیری</h4>
        <p>کلاس‌ها، تکالیف و نتایج آموزشی خودت را از همین‌جا پیگیری کن.</p>
        @can('classes.view.enrolled')
            <a class="btn s" href="#classes">مشاهده‌ی کلاس‌ها</a>
        @endcan
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">خروج از حساب</button>
        </form>
    </div>
</aside>
