<aside class="panel-sidebar admin-sidebar" data-panel-sidebar aria-label="منوی اصلی">
    <a class="panel-brand" href="{{ route('admin.dashboard') }}">
        <span class="panel-brand-mark">ش</span>
        <span class="panel-brand-copy"><strong>آکادمی شیخان</strong><small>پنل مدیریت و آموزش</small></span>
    </a>
    <button type="button" class="panel-sidebar-collapse" data-admin-sidebar-mini-toggle aria-label="جمع یا باز کردن نوار کناری" title="جمع یا باز کردن منو">⇔</button>
    <nav class="panel-nav" aria-label="منوی اصلی">
        <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">عمومی</button>
        @can('dashboard.view')
            <a href="{{ route('admin.dashboard') }}" class="panel-nav-link {{ request()->routeIs('admin.dashboard') || request()->routeIs('teacher.dashboard') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-home"/></svg></span><span>داشبورد</span></a>
        @endcan

        @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'])
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">آموزش</button>
            <a href="{{ route('admin.classes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.classes.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-classes"/></svg></span><span>کلاس‌ها و اسکای‌روم</span></a>
        @endcanany
        @canany(['courses.manage', 'courses.view.assigned'])
            <a href="{{ route('admin.courses.index') }}" class="panel-nav-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-courses"/></svg></span><span>دوره‌ها</span></a>
        @endcanany
        @canany(['integrations.manage', 'videos.manage.assigned'])
            <a href="{{ route('admin.spotplayer.index') }}" class="panel-nav-link {{ request()->routeIs('admin.spotplayer.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-lock"/></svg></span><span>ویدیوهای کلاس</span></a>
        @endcanany
        @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
            <a href="{{ route('admin.attendance.index') }}" class="panel-nav-link {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-check"/></svg></span><span>حضور و غیاب</span></a>
        @endcanany
        @canany(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned'])
            <a href="{{ route('admin.homework.index') }}" class="panel-nav-link {{ request()->routeIs('admin.homework.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-edit"/></svg></span><span>تکالیف</span></a>
        @endcanany
        @canany(['exams.manage', 'exams.manage.assigned'])
            <a href="{{ route('admin.exams.index') }}" class="panel-nav-link {{ request()->routeIs('admin.exams.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-exams"/></svg></span><span>آزمون‌ها</span></a>
        @endcanany

        @canany(['users.manage', 'students.view.assigned'])
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">کاربران</button>
            <a href="{{ route('admin.students.index') }}" class="panel-nav-link {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-users"/></svg></span><span>دانش‌آموزان</span></a>
        @endcanany
        @can('users.manage')
            <a href="{{ route('admin.teachers.index') }}" class="panel-nav-link {{ request()->routeIs('admin.teachers.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-user"/></svg></span><span>مدرسان</span></a>
            <a href="{{ route('admin.parents.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parents.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-parents"/></svg></span><span>اولیا</span></a>
        @endcan

        @canany(['resources.manage', 'resources.manage.assigned'])
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">منابع و فروش</button>
            <a href="{{ route('admin.resources.index') }}" class="panel-nav-link {{ request()->routeIs('admin.resources.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-bag"/></svg></span><span>منابع و فروشگاه</span></a>
        @endcanany

        @can('consultations.manage')
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">ارتباطات</button>
            <a href="{{ route('admin.consultations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.consultations.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-phone"/></svg></span><span>مشاوره و جلسات</span></a>
        @endcan
        @canany(['messages.view.all', 'messages.view.assigned'])
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">ارتباطات</button>
            <a href="{{ route('admin.messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-msg"/></svg></span><span>پیام‌ها</span></a>
        @endcanany
        @canany(['announcements.manage', 'announcements.view'])
            <a href="{{ route('admin.announcements.index') }}" class="panel-nav-link {{ request()->routeIs('admin.announcements.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-bell"/></svg></span><span>اطلاع‌رسانی</span></a>
        @endcanany

        @can('seo.manage')
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">محتوا و بهینه‌سازی</button>
            <a href="{{ route('admin.seo.index') }}" class="panel-nav-link {{ request()->routeIs('admin.seo.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-star"/></svg></span><span>مدیریت سئو</span></a>
        @endcan

        @can('integrations.manage')
            <button type="button" class="panel-nav-heading" data-admin-nav-group-toggle aria-expanded="false">سرویس‌های خارجی</button>
            <a href="{{ route('admin.quiz24.index') }}" class="panel-nav-link {{ request()->routeIs('admin.quiz24.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-star"/></svg></span><span>کوییز ۲۴</span></a>
        @endcan
    </nav>
    <div class="panel-sidebar-footer">
        <div class="panel-user panel-user-sidebar"><span class="panel-user-avatar">{{ mb_substr($adminUser->name, 0, 1) }}</span><span class="panel-user-meta"><strong class="panel-user-name">{{ $adminUser->name }}</strong><small class="panel-user-role">{{ $adminUser->roles->first()?->label ?? 'کاربر آکادمی' }}</small></span></div>
    </div>
</aside>
