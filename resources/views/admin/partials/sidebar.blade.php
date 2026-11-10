<aside class="panel-sidebar" data-panel-sidebar>
    <div class="panel-brand">
        <div class="panel-brand-mark">ش</div>
        <div>
            <strong>آکادمی شیخان</strong>
            <small>
                @if (auth()->user()->hasRole('admin'))
                    مدیریت سامانه
                @elseif (auth()->user()->hasRole('teacher'))
                    فضای کاری مدرس
                @elseif (auth()->user()->hasRole('student'))
                    پنل دانش‌آموز
                @else
                    پنل اولیا
                @endif
            </small>
        </div>
    </div>

    <nav class="panel-nav" aria-label="منوی اصلی">
        @if (auth()->user()->hasRole('admin'))
            <div class="panel-nav-heading">نمای کلی</div>
            @can('dashboard.view')
                <a href="{{ route('admin.dashboard') }}" class="panel-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
                    <span class="panel-nav-icon">▦</span><span>داشبورد مدیریت</span>
                </a>
            @endcan

            <div class="panel-nav-heading">آموزش</div>
            @can('classes.manage')
                <a href="{{ route('admin.classes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.classes.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">▣</span><span>کلاس‌ها و جلسات</span></a>
            @endcan
            @can('courses.manage')
                <a href="{{ route('admin.courses.index') }}" class="panel-nav-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">▤</span><span>دوره‌ها</span></a>
            @endcan
            @can('attendance.manage.all')
                <a href="{{ route('admin.attendance.index') }}" class="panel-nav-link {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">✓</span><span>حضور و غیاب</span></a>
            @endcan
            @can('homework.manage.all')
                <a href="{{ route('admin.homework.index') }}" class="panel-nav-link {{ request()->routeIs('admin.homework.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">✎</span><span>تکالیف و بررسی</span></a>
            @endcan
            @can('exams.manage')
                <a href="{{ route('admin.exams.index') }}" class="panel-nav-link {{ request()->routeIs('admin.exams.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">★</span><span>آزمون‌ها و نتایج</span></a>
            @endcan

            <div class="panel-nav-heading">کاربران</div>
            @can('users.manage')
                <a href="{{ route('admin.students.index') }}" class="panel-nav-link {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">♙</span><span>دانش‌آموزان</span></a>
                <a href="{{ route('admin.teachers.index') }}" class="panel-nav-link {{ request()->routeIs('admin.teachers.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">♟</span><span>مدرسان</span></a>
                <a href="{{ route('admin.parents.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parents.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">♧</span><span>اولیا</span></a>
            @endcan

            <div class="panel-nav-heading">منابع و ارتباطات</div>
            @can('resources.manage')
                <a href="{{ route('admin.resources.index') }}" class="panel-nav-link {{ request()->routeIs('admin.resources.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">▧</span><span>منابع و محصولات</span></a>
            @endcan
            @can('consultations.manage')
                <a href="{{ route('admin.consultations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.consultations.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">◷</span><span>مشاوره و جلسات اولیا</span></a>
            @endcan
            @can('messages.view.all')
                <a href="{{ route('admin.messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">✉</span><span>پیام‌ها</span></a>
            @endcan
            @can('announcements.manage')
                <a href="{{ route('admin.announcements.index') }}" class="panel-nav-link {{ request()->routeIs('admin.announcements.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">⚑</span><span>اطلاعیه‌ها</span></a>
            @endcan

            <div class="panel-nav-heading">سرویس‌های خارجی</div>
            @can('integrations.manage')
                <a href="{{ route('admin.spotplayer.index') }}" class="panel-nav-link {{ request()->routeIs('admin.spotplayer.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">▷</span><span>اسپات پلیر</span></a>
                <a href="{{ route('admin.quiz24.index') }}" class="panel-nav-link {{ request()->routeIs('admin.quiz24.*') ? 'is-active' : '' }}"><span class="panel-nav-icon">◈</span><span>کوییز ۲۴</span></a>
            @endcan
        @elseif (auth()->user()->hasRole('teacher'))
            <div class="panel-nav-heading">فضای کاری مدرس</div>
            @can('dashboard.view')
                <a href="{{ route('teacher.dashboard') }}#overview" class="panel-nav-link is-active"><span class="panel-nav-icon">▦</span><span>داشبورد من</span></a>
            @endcan
            @can('classes.view.assigned')
                <a href="{{ route('teacher.dashboard') }}#classes" class="panel-nav-link"><span class="panel-nav-icon">▣</span><span>کلاس‌های من</span></a>
            @endcan
            @can('courses.view.assigned')
                <a href="{{ route('teacher.dashboard') }}#courses" class="panel-nav-link"><span class="panel-nav-icon">▤</span><span>دوره‌های من</span></a>
            @endcan
            @can('students.view.assigned')
                <a href="{{ route('teacher.dashboard') }}#students" class="panel-nav-link"><span class="panel-nav-icon">♙</span><span>دانش‌آموزان کلاس‌ها</span></a>
            @endcan
            @can('attendance.view.assigned')
                <a href="{{ route('teacher.dashboard') }}#attendance" class="panel-nav-link"><span class="panel-nav-icon">✓</span><span>گزارش حضور و غیاب</span></a>
            @endcan
            @can('homework.view.assigned')
                <a href="{{ route('teacher.dashboard') }}#homework" class="panel-nav-link"><span class="panel-nav-icon">✎</span><span>تکالیف و تصحیح</span></a>
            @endcan
            @can('announcements.view')
                <a href="{{ route('teacher.dashboard') }}#announcements" class="panel-nav-link"><span class="panel-nav-icon">⚑</span><span>اطلاعیه‌ها</span></a>
            @endcan
        @elseif (auth()->user()->hasRole('student'))
            <div class="panel-nav-heading">فضای یادگیری</div>
            @can('dashboard.view')
                <a href="{{ route('student.dashboard') }}#overview" class="panel-nav-link is-active"><span class="panel-nav-icon">▦</span><span>داشبورد من</span></a>
            @endcan
            @can('classes.view.enrolled')
                <a href="{{ route('student.dashboard') }}#classes" class="panel-nav-link"><span class="panel-nav-icon">▣</span><span>کلاس‌های من</span></a>
            @endcan
            @can('homework.view.own')
                <a href="{{ route('student.dashboard') }}#homework" class="panel-nav-link"><span class="panel-nav-icon">✎</span><span>تکالیف من</span></a>
            @endcan
            @can('attendance.view.own')
                <a href="{{ route('student.dashboard') }}#attendance" class="panel-nav-link"><span class="panel-nav-icon">✓</span><span>حضور و غیاب</span></a>
            @endcan
            @can('exams.view.own')
                <a href="{{ route('student.dashboard') }}#results" class="panel-nav-link"><span class="panel-nav-icon">★</span><span>نتایج آزمون‌ها</span></a>
            @endcan
            @can('resources.view.enrolled')
                <a href="{{ route('student.dashboard') }}#resources" class="panel-nav-link"><span class="panel-nav-icon">▧</span><span>منابع آموزشی</span></a>
            @endcan
            @can('announcements.view')
                <a href="{{ route('student.dashboard') }}#announcements" class="panel-nav-link"><span class="panel-nav-icon">⚑</span><span>اطلاعیه‌ها</span></a>
            @endcan
        @else
            <div class="panel-nav-heading">پیگیری آموزشی فرزندان</div>
            @can('dashboard.view')
                <a href="{{ route('guardian.dashboard') }}#overview" class="panel-nav-link is-active"><span class="panel-nav-icon">▦</span><span>داشبورد</span></a>
            @endcan
            @can('students.view.linked')
                <a href="{{ route('guardian.dashboard') }}#children" class="panel-nav-link"><span class="panel-nav-icon">♙</span><span>فرزندان من</span></a>
            @endcan
            @can('attendance.view.children')
                <a href="{{ route('guardian.dashboard') }}#attendance" class="panel-nav-link"><span class="panel-nav-icon">✓</span><span>حضور و غیاب</span></a>
            @endcan
            @can('homework.view.children')
                <a href="{{ route('guardian.dashboard') }}#homework" class="panel-nav-link"><span class="panel-nav-icon">✎</span><span>تکالیف</span></a>
            @endcan
            @can('exams.view.children')
                <a href="{{ route('guardian.dashboard') }}#results" class="panel-nav-link"><span class="panel-nav-icon">★</span><span>نتایج آزمون‌ها</span></a>
            @endcan
            @can('announcements.view')
                <a href="{{ route('guardian.dashboard') }}#announcements" class="panel-nav-link"><span class="panel-nav-icon">⚑</span><span>اطلاعیه‌ها</span></a>
            @endcan
        @endif
    </nav>

    <div class="panel-sidebar-footer">فضای کاری اختصاصی · آکادمی شیخان</div>
</aside>
