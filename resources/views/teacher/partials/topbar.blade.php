@php
    $teacherUser = auth()->user();
    $teacherNotifications = $teacherUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
    $teacherUnreadCount = $teacherUser?->unreadNotifications()->count() ?? 0;
@endphp

<header class="top teacher-topbar" data-teacher-topbar>
    <div class="topbar-start">
        <button type="button" class="ic teacher-nav-toggle" id="teacherNavToggle"
            aria-controls="teacherSidebar" aria-expanded="false" aria-label="باز کردن منوی اصلی">
            <svg class="topbar-nav-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            <span class="teacher-nav-toggle-label">منو</span>
        </button>

        <a class="logo" href="{{ route('teacher.dashboard') }}" aria-label="داشبورد مدرس">
            <span class="logo-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"></path>
                    <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"></path>
                </svg>
            </span>
            <span class="logo-copy">
                <span class="logo-brand">آکادمی <span>شیخان</span></span>
                <span class="logo-role">پنل مدرسان</span>
            </span>
        </a>
    </div>

    <div class="topbar-center">
        <div class="teacher-global-search">
            <svg class="search-svg" viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input class="sr" id="globalSearch" type="search" placeholder="جست‌وجوی سریع در پنل…"
                autocomplete="off" aria-label="جست‌وجوی سریع در پنل" aria-controls="teacherSearchLinks">
            <button type="button" class="teacher-search-clear" id="teacherSearchClear"
                aria-label="پاک کردن جست‌وجو" hidden>×</button>
            <span id="teacherSearchResults" class="sr-only" role="status" aria-live="polite"></span>

            <div class="teacher-search-links" id="teacherSearchLinks" hidden>
                <strong>رفتن به بخش</strong>
                @if($teacherUser?->hasPermissionTo('classes.view.assigned'))
                    <a data-search-route href="{{ route('teacher.classes') }}">کلاس‌ها و دوره‌ها</a>
                    <a data-search-route href="{{ route('teacher.live') }}">کلاس آنلاین</a>
                    <a data-search-route href="{{ route('teacher.meetings') }}">جلسات با اولیا</a>
                @endif
                @if($teacherUser?->hasPermissionTo('homework.view.assigned'))
                    <a data-search-route href="{{ route('teacher.homework') }}">تکالیف و ارزیابی</a>
                @endif
                @if($teacherUser?->hasPermissionTo('attendance.view.assigned'))
                    <a data-search-route href="{{ route('teacher.attendance') }}">حضور و غیاب</a>
                @endif
                @if($teacherUser?->hasPermissionTo('messages.view.assigned'))
                    <a data-search-route href="{{ route('teacher.messages.index') }}">پیام‌ها</a>
                @endif
                @if($teacherUser?->hasPermissionTo('consultations.create'))
                    <a data-search-route href="{{ route('teacher.consultations.index') }}">درخواست هماهنگی</a>
                @endif
                @if($teacherUser?->hasPermissionTo('resources.manage.assigned'))
                    <a data-search-route href="{{ route('teacher.resources') }}">منابع آموزشی</a>
                @endif
                @if($teacherUser?->hasPermissionTo('videos.manage.assigned'))
                    <a data-search-route href="{{ route('teacher.videos') }}">ویدئوها</a>
                @endif
                <a data-search-route href="{{ route('teacher.profile.edit') }}">پروفایل</a>
            </div>
        </div>
    </div>

    <div class="topbar-end">
        <div class="dots teacher-palette" role="group" aria-label="انتخاب رنگ پنل" title="تغییر تم رنگی">
            <button type="button" data-pal="" aria-label="رنگ‌بندی بنفش" aria-pressed="true" title="بنفش"></button>
            <button type="button" data-pal="g" aria-label="رنگ‌بندی سبز" aria-pressed="false" title="سبز"></button>
            <button type="button" data-pal="o" aria-label="رنگ‌بندی نارنجی" aria-pressed="false" title="نارنجی"></button>
        </div>

        <button type="button" class="ic topbar-icon-action" id="themeToggle" aria-label="فعال‌سازی تم تیره"
            aria-pressed="false" title="تغییر پوسته">
            <svg class="theme-svg theme-svg-moon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>

        <details class="notification-menu teacher-menu">
            <summary class="ic topbar-icon-action notification-summary" aria-label="اعلان‌ها، {{ $teacherUnreadCount }} اعلان خوانده‌نشده" title="اعلان‌ها">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                @if($teacherUnreadCount > 0)
                    <sup class="notification-badge">{{ $teacherUnreadCount > 99 ? '۹۹+' : $teacherUnreadCount }}</sup>
                @endif
            </summary>

            <div class="notification-pop" aria-label="فهرست اعلان‌ها">
                <div class="teacher-menu-heading">
                    <strong>اعلان‌های خوانده‌نشده</strong>
                    <span>{{ $teacherUnreadCount }}</span>
                </div>
                @forelse($teacherNotifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="notification-item">
                            <b>{{ data_get($notification->data, 'title', 'اعلان جدید') }}</b>
                            <small>{{ data_get($notification->data, 'message', 'اعلان جدیدی ثبت شده است.') }}</small>
                            <small>{{ $notification->created_at?->diffForHumans() }}</small>
                        </button>
                    </form>
                @empty
                    <p class="teacher-menu-empty">اعلان خوانده‌نشده‌ای ندارید.</p>
                @endforelse

                @if($teacherUnreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">
                        @csrf
                        @method('PATCH')
                        <button type="submit" class="notification-read-all">خواندن همه اعلان‌ها</button>
                    </form>
                @endif
            </div>
        </details>

        <details class="teacher-profile-menu teacher-menu">
            <summary class="teacher-profile-trigger" aria-label="منوی حساب کاربری" title="حساب کاربری">
                <span class="teacher-profile-avatar">
                    @if($teacherUser?->avatarUrl())
                        <img src="{{ $teacherUser->avatarUrl() }}" alt="">
                    @else
                        {{ mb_substr((string) $teacherUser?->name, 0, 1) }}
                    @endif
                </span>
                <span class="teacher-profile-info">
                    <span class="teacher-profile-name">{{ $teacherUser?->name }}</span>
                    <span class="teacher-profile-sub">مدرس</span>
                </span>
                <svg class="teacher-profile-chevron" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </summary>

            <div class="teacher-profile-pop">
                <a href="{{ route('teacher.profile.edit') }}">
                    <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                    ویرایش پروفایل
                </a>
                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="teacher-avatar-upload">
                        <span>تغییر تصویر پروفایل</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                    <button type="submit" class="teacher-avatar-submit">بارگذاری تصویر</button>
                </form>
                <form method="POST" action="{{ route('logout') }}" class="teacher-profile-logout">
                    @csrf
                    <button type="submit">
                        <svg viewBox="0 0 24 24" width="15" height="15" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        خروج از حساب
                    </button>
                </form>
            </div>
        </details>
    </div>
</header>
