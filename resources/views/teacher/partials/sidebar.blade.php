<aside class="panel-sidebar admin-sidebar teacher-sidebar" data-panel-sidebar aria-label="منوی مدرس">
    <a class="panel-brand" href="{{ route('teacher.dashboard') }}">
        <span class="panel-brand-mark">ش</span>
        <span class="panel-brand-copy"><strong>آکادمی شیخان</strong><small>فضای کاری مدرس</small></span>
    </a>
    <nav class="panel-nav" aria-label="بخش‌های مدرس">
        <a href="{{ route('teacher.dashboard') }}" class="panel-nav-link {{ request()->routeIs('teacher.dashboard') ? 'is-active' : '' }}">
            <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-home"/></svg></span><span>داشبورد</span>
        </a>
        @canany(['classes.view.assigned', 'classes.manage.assigned'])
            <a data-tone="learning" href="{{ route('admin.classes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.classes.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-classes"/></svg></span><span>کلاس‌ها و جلسات</span>
            </a>
        @endcanany
        @canany(['courses.view.assigned', 'courses.content.manage.assigned'])
            <a data-tone="learning" href="{{ route('admin.courses.index') }}" class="panel-nav-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-courses"/></svg></span><span>محتوای آموزشی</span>
            </a>
        @endcanany
        @canany(['attendance.manage.assigned', 'attendance.view.assigned'])
            <a data-tone="presence" href="{{ route('admin.attendance.index') }}" class="panel-nav-link {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-check"/></svg></span><span>حضور و غیاب</span>
            </a>
        @endcanany
        @canany(['homework.manage.assigned', 'homework.view.assigned', 'homework.grade'])
            <a data-tone="work" href="{{ route('admin.homework.index') }}" class="panel-nav-link {{ request()->routeIs('admin.homework*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-edit"/></svg></span><span>تکالیف و بررسی</span>
            </a>
        @endcanany
        @can('consultations.create')
            <a data-tone="talk" href="{{ route('teacher.consultations.index') }}" class="panel-nav-link {{ request()->routeIs('teacher.consultations.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-phone"/></svg></span><span>درخواست مشاوره</span>
            </a>
        @endcan
        @canany(['messages.view.assigned', 'messages.reply'])
            <a data-tone="talk" href="{{ route('admin.messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-msg"/></svg></span><span>کارتابل پیام‌ها</span>
            </a>
            <a data-tone="talk" href="{{ route('teacher.messages.create') }}" class="panel-nav-link {{ request()->routeIs('teacher.messages.create') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-edit"/></svg></span><span>ارسال پیام</span>
            </a>
        @endcanany
        @canany(['classes.manage.assigned', 'classes.view.assigned'])
            <a data-tone="people" href="{{ route('admin.parent-meetings.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parent-meetings.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-parents"/></svg></span><span>جلسات اولیا</span>
            </a>
        @endcanany
        @canany(['resources.manage.assigned'])
            <a data-tone="library" href="{{ route('admin.resources.index') }}" class="panel-nav-link {{ request()->routeIs('admin.resources.*') ? 'is-active' : '' }}">
                <span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-bag"/></svg></span><span>منابع آموزشی</span>
            </a>
        @endcanany
    </nav>
    <div class="panel-sidebar-footer">
        <div class="panel-user panel-user-sidebar">
            <span class="panel-user-avatar">{{ mb_substr($adminUser->name, 0, 1) }}</span>
            <span class="panel-user-meta"><strong class="panel-user-name">{{ $adminUser->name }}</strong><small class="panel-user-role">مدرس</small></span>
        </div>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="panel-nav-link teacher-logout" type="submit"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-exit"/></svg></span><span>خروج از حساب</span></button></form>
    </div>
</aside>
