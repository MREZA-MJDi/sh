@php
    $portalRole = request()->routeIs('guardian.*') ? 'parent' : 'student';
    $portalUser = auth()->user();
    $nextClass = $portalRole === 'student'
        ? ($classrooms->firstWhere('status', 'active') ?? null)
        : ($classrooms->firstWhere('status', 'active') ?? null);
@endphp

<aside class="side" id="side" aria-label="{{ $portalRole === 'student' ? 'منوی دانش‌آموز' : 'منوی اولیا' }}">
    <div class="me">
        <div class="av">
            <svg viewBox="0 0 120 120" width="100%" height="100%" aria-hidden="true">
                <use href="#avatar"></use>
            </svg>
        </div>
        <h3>{{ $portalUser?->name ?? 'کاربر آکادمی' }}</h3>
        <small>
            @if($portalRole === 'student')
                دانش‌آموز{{ $portalUser?->grade ? ' · '.$portalUser->grade : '' }}
            @else
                کارتابل اولیا
            @endif
        </small>
    </div>

    <nav class="nav" aria-label="بخش‌های کارتابل">
        <a href="#overview" class="on" aria-current="page" data-nav-link>
            <x-icon name="home"/> {{ $portalRole === 'student' ? 'خانه‌ی یادگیری' : 'خلاصه‌ی وضعیت' }}
        </a>

        @if($portalRole === 'student')
            @can('classes.view.enrolled')
                <h6>یادگیری</h6>
                <a href="#schedule" data-nav-link><x-icon name="cal"/> برنامه‌ی کلاس‌ها</a>
                <a href="#classes" data-nav-link><x-icon name="book"/> کلاس‌های من</a>
            @endcan
            @can('homework.view.own')
                <h6>تکالیف و ارزیابی</h6>
                <a href="#homework" data-nav-link><x-icon name="task"/> تکالیف</a>
            @endcan
            @can('exams.view.own')
                <a href="#results" data-nav-link><x-icon name="exam"/> نتایج آزمون‌ها</a>
            @endcan
            @can('attendance.view.own')
                <a href="#attendance" data-nav-link><x-icon name="chart"/> حضور و غیاب</a>
            @endcan
            @can('resources.view.enrolled')
                <h6>منابع آموزشی</h6>
                <a href="#resources" data-nav-link><x-icon name="file"/> جزوه‌ها و منابع</a>
            @endcan
        @else
            <h6>وضعیت فرزند</h6>
            <a href="#children" data-nav-link><x-icon name="users"/> فرزندان متصل‌شده</a>
            @can('homework.view.children')
                <a href="#homework" data-nav-link><x-icon name="task"/> تکالیف</a>
            @endcan
            @can('exams.view.children')
                <a href="#results" data-nav-link><x-icon name="exam"/> نتایج آزمون‌ها</a>
            @endcan
            @can('attendance.view.children')
                <a href="#attendance" data-nav-link><x-icon name="cal"/> حضور و غیاب</a>
            @endcan
            @can('resources.view.enrolled')
                <a href="#resources" data-nav-link><x-icon name="file"/> منابع آموزشی</a>
            @endcan
        @endif

        @can('announcements.view')
            <h6>اطلاع‌رسانی</h6>
            <a href="#announcements" data-nav-link><x-icon name="bell"/> اطلاعیه‌های آکادمی</a>
        @endcan
    </nav>

    <div class="prem">
        @if($portalRole === 'student')
            <h4>کارتابل آموزشی</h4>
            <p>وضعیت کلاس‌ها، تکالیف و نتایج آموزشی خودت را پیگیری کن.</p>
            @can('classes.view.enrolled')
                <a class="btn s" href="#classes">مشاهده‌ی کلاس‌ها</a>
            @endcan
        @else
            <h4>پیگیری تحصیلی</h4>
            <p>اطلاعات فرزند متصل به حساب خود را مشاهده کنید.</p>
            @if($selectedChild)
                <a class="btn s" href="#children">انتخاب فرزند</a>
            @endif
        @endif

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">خروج از حساب</button>
        </form>
    </div>
</aside>
