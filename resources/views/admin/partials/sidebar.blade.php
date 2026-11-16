<aside class="panel-sidebar admin-sidebar" data-panel-sidebar aria-label="منوی اصلی">
    <a class="panel-brand" href="{{ $adminUser && $adminUser->hasRole('seo') && ! $adminUser->hasPermissionTo('dashboard.view') ? route('admin.seo.index') : route('admin.dashboard') }}">
        <span class="panel-brand-mark">ش</span>
        <span class="panel-brand-copy"><strong>آکادمی شیخان</strong><small>{{ $adminUser && $adminUser->hasRole('seo') && ! $adminUser->hasRole('admin') ? 'پنل سئو و محتوا' : 'پنل مدیریت و آموزش' }}</small></span>
    </a>
    <button type="button" class="panel-sidebar-collapse" data-admin-sidebar-mini-toggle aria-label="جمع یا باز کردن نوار کناری" title="جمع یا باز کردن منو">⇔</button>

    <nav class="panel-nav" aria-label="منوی اصلی">
        @can('dashboard.view')
            <button type="button" class="panel-nav-heading" data-tone="system" data-admin-nav-group-toggle aria-expanded="false">نمای کلی</button>
            <a data-tone="system" href="{{ route('admin.dashboard') }}" class="panel-nav-link {{ request()->routeIs('admin.dashboard') || request()->routeIs('teacher.dashboard') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-home"/></svg></span><span>داشبورد و آمار</span></a>
        @endcan

        @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned', 'courses.manage', 'courses.view.assigned', 'integrations.manage', 'videos.manage.assigned', 'attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned', 'homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned', 'exams.manage', 'exams.manage.assigned'])
            <button type="button" class="panel-nav-heading" data-tone="learning" data-admin-nav-group-toggle aria-expanded="false">آموزش و ارزیابی</button>
            @canany(['classes.manage', 'classes.view.assigned', 'classes.manage.assigned'])
                <a data-tone="learning" href="{{ route('admin.classes.index') }}" class="panel-nav-link {{ request()->routeIs('admin.classes.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-classes"/></svg></span><span>کلاس‌ها و اسکای‌روم</span></a>
            @endcanany
            @canany(['classes.manage', 'classes.manage.assigned'])
                <a data-tone="learning" href="{{ route('admin.parent-meetings.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parent-meetings.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-parents"/></svg></span><span>جلسات والدین</span></a>
            @endcanany
            @canany(['courses.manage', 'courses.view.assigned'])
                <a data-tone="learning" href="{{ route('admin.courses.index') }}" class="panel-nav-link {{ request()->routeIs('admin.courses.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-courses"/></svg></span><span>دوره‌ها و محتوای آموزشی</span></a>
            @endcanany
            @canany(['integrations.manage', 'videos.manage.assigned'])
                <a data-tone="learning" href="{{ route('admin.spotplayer.index') }}" class="panel-nav-link {{ request()->routeIs('admin.spotplayer.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-lock"/></svg></span><span>ویدیوهای کلاس و SpotPlayer</span></a>
            @endcanany
            @canany(['attendance.manage.all', 'attendance.manage.assigned', 'attendance.view.assigned'])
                <a data-tone="presence" href="{{ route('admin.attendance.index') }}" class="panel-nav-link {{ request()->routeIs('admin.attendance.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-check"/></svg></span><span>حضور و غیاب</span></a>
            @endcanany
            @canany(['homework.manage.all', 'homework.manage.assigned', 'homework.view.assigned'])
                <a data-tone="work" href="{{ route('admin.homework.index') }}" class="panel-nav-link {{ request()->routeIs('admin.homework.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-edit"/></svg></span><span>تکالیف و تصحیح</span></a>
            @endcanany
            @canany(['exams.manage', 'exams.manage.assigned'])
                <a data-tone="learning" href="{{ route('admin.exams.index') }}" class="panel-nav-link {{ request()->routeIs('admin.exams.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-exams"/></svg></span><span>آزمون‌ها و نتایج</span></a>
            @endcanany
        @endcanany

        @canany(['users.manage', 'students.view.assigned'])
            <button type="button" class="panel-nav-heading" data-tone="people" data-admin-nav-group-toggle aria-expanded="false">مدیریت کاربران</button>
            <a data-tone="people" href="{{ route('admin.students.index') }}" class="panel-nav-link {{ request()->routeIs('admin.students.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-users"/></svg></span><span>مدیریت دانش‌آموزان</span></a>
            @can('users.manage')
                <a data-tone="people" href="{{ route('admin.teachers.index') }}" class="panel-nav-link {{ request()->routeIs('admin.teachers.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-user"/></svg></span><span>مدیریت مدرسان</span></a>
                <a data-tone="people" href="{{ route('admin.parents.index') }}" class="panel-nav-link {{ request()->routeIs('admin.parents.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-parents"/></svg></span><span>مدیریت اولیا</span></a>
            @endcan
        @endcanany

        @canany(['resources.manage', 'resources.manage.assigned'])
            <button type="button" class="panel-nav-heading" data-tone="library" data-admin-nav-group-toggle aria-expanded="false">منابع و فروش</button>
            <a data-tone="library" href="{{ route('admin.resources.index') }}" class="panel-nav-link {{ request()->routeIs('admin.resources.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-bag"/></svg></span><span>منابع و فروشگاه</span></a>
        @endcanany

        @canany(['consultations.manage', 'messages.view.all', 'messages.view.assigned', 'announcements.manage', 'announcements.view'])
            <button type="button" class="panel-nav-heading" data-tone="talk" data-admin-nav-group-toggle aria-expanded="false">ارتباطات و اطلاع‌رسانی</button>
            @can('consultations.manage')
                <a data-tone="talk" href="{{ route('admin.consultations.index') }}" class="panel-nav-link {{ request()->routeIs('admin.consultations.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-phone"/></svg></span><span>مشاوره و جلسات</span></a>
            @endcan
            @canany(['messages.view.all', 'messages.view.assigned'])
                <a data-tone="talk" href="{{ route('admin.messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.messages.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-msg"/></svg></span><span>پیام‌ها</span></a>
            @endcanany
            @can('messages.view.all')
                <a data-tone="talk" href="{{ route('admin.contact-messages.index') }}" class="panel-nav-link {{ request()->routeIs('admin.contact-messages.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-msg"/></svg></span><span>فرم تماس سایت</span></a>
            @endcan
            @canany(['announcements.manage', 'announcements.view'])
                <a data-tone="talk" href="{{ route('admin.announcements.index') }}" class="panel-nav-link {{ request()->routeIs('admin.announcements.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-bell"/></svg></span><span>اطلاع‌رسانی</span></a>
            @endcanany
        @endcanany

        @can('integrations.manage')
            <button type="button" class="panel-nav-heading" data-tone="system" data-admin-nav-group-toggle aria-expanded="false">اتصال سرویس‌ها</button>
            <a data-tone="system" href="{{ route('admin.quiz24.index') }}" class="panel-nav-link {{ request()->routeIs('admin.quiz24.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-star"/></svg></span><span>کوییز ۲۴</span></a>
        @endcan

        {{-- SEO is a separate internal workspace. The client/admin role must never see this entry. --}}
        @can('seo.manage')
            <button type="button" class="panel-nav-heading" data-tone="system" data-admin-nav-group-toggle aria-expanded="false">فضای کاری سئو</button>
            <a data-tone="system" href="{{ route('admin.seo.index') }}" class="panel-nav-link {{ request()->routeIs('admin.seo.*') ? 'is-active' : '' }}"><span class="panel-nav-icon"><svg class="admin-icon"><use href="#admin-star"/></svg></span><span>مدیریت سئو</span></a>
        @endcan
    </nav>

    <div class="panel-sidebar-footer">
        <div class="panel-user panel-user-sidebar"><span class="panel-user-avatar">{{ mb_substr($adminUser->name, 0, 1) }}</span><span class="panel-user-meta"><strong class="panel-user-name">{{ $adminUser->name }}</strong><small class="panel-user-role">{{ $adminUser->roles->first()?->label ?? 'کاربر آکادمی' }}</small></span></div>
    </div>
</aside>
