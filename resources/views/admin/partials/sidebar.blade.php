<aside class="panel-sidebar" data-panel-sidebar>
    <div class="panel-brand">
        <div class="panel-brand-mark">ش</div>
        <div>
            <strong>آکادمی شیخان</strong>
            <small>مدیریت و پشتیبانی سامانه</small>
        </div>
    </div>

    <nav class="panel-nav" aria-label="منوی اصلی">
        <div class="panel-nav-heading">نمای کلی</div>
        <a href="{{ route('admin.dashboard') }}" class="panel-nav-link {{ request()->routeIs('admin.dashboard') ? 'is-active' : '' }}">
            <span class="panel-nav-icon">▦</span><span>داشبورد</span>
        </a>

        <div class="panel-nav-heading">آموزش</div>
        @can('classes.manage')
            <a href="{{ route('admin.classes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.classes.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">▣</span><span>کلاس‌ها و جلسات</span>
            </a>
        @endcan
        @can('courses.manage')
            <a href="{{ route('admin.courses.index') }}" class="panel-nav-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">▤</span><span>دوره‌ها</span>
            </a>
        @endcan
        @can('attendance.manage.all')
            <a href="{{ route('admin.attendance.index') }}" class="panel-nav-link {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">✓</span><span>حضور و غیاب</span>
            </a>
        @endcan
        @can('homework.manage.all')
            <a href="{{ route('admin.homework.index') }}" class="panel-nav-link {{ request()->routeIs('admin.homework.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">✎</span><span>تکالیف و بررسی</span>
            </a>
        @endcan
        @can('exams.manage')
            <a href="{{ route('admin.exams.index') }}" class="panel-nav-link {{ request()->routeIs('admin.exams.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">★</span><span>آزمون‌ها و نتایج</span>
            </a>
        @endcan

        <div class="panel-nav-heading">کاربران</div>
        @can('users.manage')
            <a href="{{ route('admin.students.index') }}" class="panel-nav-link {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">♙</span><span>دانش‌آموزان</span>
            </a>
            <a href="{{ route('admin.teachers.index') }}" class="panel-nav-link {{ request()->routeIs('admin.teachers.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">♟</span><span>مدرسان</span>
            </a>
            <a href="{{ route('admin.parents.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parents.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">♧</span><span>اولیا</span>
            </a>
        @endcan

        <div class="panel-nav-heading">منابع و فروش</div>
        @can('resources.manage')
            <a href="{{ route('admin.resources.index') }}" class="panel-nav-link {{ request()->routeIs('admin.resources.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">▧</span><span>منابع و محصولات</span>
            </a>
        @endcan

        <div class="panel-nav-heading">ارتباطات</div>
        @can('consultations.manage')
            <a href="{{ route('admin.consultations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.consultations.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">◷</span><span>مشاوره و جلسات اولیا</span>
            </a>
        @endcan
        @can('messages.view.all')
            <a href="{{ route('admin.messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">✉</span><span>پیام‌ها</span>
            </a>
        @endcan
        @can('announcements.manage')
            <a href="{{ route('admin.announcements.index') }}" class="panel-nav-link {{ request()->routeIs('admin.announcements.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">⚑</span><span>اطلاعیه‌ها</span>
            </a>
        @endcan

        <div class="panel-nav-heading">سرویس‌های خارجی</div>
        @can('integrations.manage')
            <a href="{{ route('admin.spotplayer.index') }}" class="panel-nav-link {{ request()->routeIs('admin.spotplayer.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">▷</span><span>اسپات پلیر</span>
            </a>
            <a href="{{ route('admin.quiz24.index') }}" class="panel-nav-link {{ request()->routeIs('admin.quiz24.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon">◈</span><span>کوییز ۲۴</span>
            </a>
        @endcan
    </nav>

    <div class="panel-sidebar-footer">نسخه مدیریتی · آکادمی شیخان</div>
</aside>
