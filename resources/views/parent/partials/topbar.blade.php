@php
    $parentUser = auth()->user();
    $parentNotifications = $parentUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
    $parentUnreadCount = $parentUser?->unreadNotifications()->count() ?? 0;
@endphp

<header class="top parent-topbar" data-parent-topbar>
    <div class="topbar-start">
        <button type="button" class="ic parent-nav-toggle" id="parentNavToggle" aria-controls="parentSidebar" aria-expanded="false" aria-label="باز کردن منوی اصلی">
            <svg class="topbar-nav-icon" viewBox="0 0 24 24" width="20" height="20" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
            <span class="parent-nav-toggle-label">منو</span>
        </button>

        <a class="logo" href="{{ route('guardian.dashboard') }}" aria-label="داشبورد اولیا">
            <span class="logo-badge" aria-hidden="true">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                    <circle cx="9" cy="7" r="4"></circle>
                    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                </svg>
            </span>
            <span class="logo-copy">
                <span class="logo-brand">آکادمی <span>شیخان</span></span>
                <span class="logo-role">پنل اولیا و خانواده</span>
            </span>
        </a>
    </div>

    <div class="topbar-center">
        <div class="parent-global-search">
            <svg class="search-svg" viewBox="0 0 24 24" width="17" height="17" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            <input class="sr" id="globalSearch" type="search" placeholder="جستجو در همین صفحه…"
                autocomplete="off" aria-label="جست‌وجو در همین صفحه">
            <button type="button" id="parentSearchClear" class="parent-search-clear" aria-label="پاک کردن جست‌وجو" hidden>×</button>
            <span id="parentSearchResults" class="sr-only" role="status" aria-live="polite"></span>
        </div>
    </div>

    <div class="topbar-end">
        <div class="dots parent-palette" role="group" aria-label="انتخاب رنگ پنل" title="تغییر تم رنگی">
            <button type="button" data-pal="" aria-label="رنگ‌بندی بنفش" aria-pressed="true" title="بنفش"></button>
            <button type="button" data-pal="g" aria-label="رنگ‌بندی سبز" aria-pressed="false" title="سبز"></button>
            <button type="button" data-pal="o" aria-label="رنگ‌بندی نارنجی" aria-pressed="false" title="نارنجی"></button>
        </div>

        <button type="button" class="ic topbar-icon-action" id="themeToggle" aria-label="فعال‌سازی تم تیره" aria-pressed="false" title="تغییر پوسته">
            <svg class="theme-svg theme-svg-moon" viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"></path>
            </svg>
        </button>

        <details class="parent-menu parent-notification-menu">
            <summary class="ic topbar-icon-action notification-summary" aria-label="اعلان‌ها، {{ $parentUnreadCount }} اعلان خوانده‌نشده" title="اعلان‌ها">
                <svg viewBox="0 0 24 24" width="18" height="18" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                    <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                </svg>
                @if($parentUnreadCount > 0)
                    <sup class="notification-badge">{{ $parentUnreadCount > 99 ? '۹۹+' : $parentUnreadCount }}</sup>
                @endif
            </summary>
            <div class="parent-menu-pop parent-notification-pop">
                <div class="parent-menu-heading">
                    <strong>اعلان‌های خوانده‌نشده</strong>
                    <span>{{ $parentUnreadCount }}</span>
                </div>
                @forelse($parentNotifications as $notification)
                    <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                        @csrf @method('PATCH')
                        <button type="submit" class="parent-notification-item">
                            <b>{{ data_get($notification->data, 'title', 'اعلان جدید') }}</b>
                            <small>{{ data_get($notification->data, 'message', 'اعلان جدیدی ثبت شده است.') }}</small>
                            <small>{{ $notification->created_at?->diffForHumans() }}</small>
                        </button>
                    </form>
                @empty
                    <p class="parent-menu-empty">اعلان خوانده‌نشده‌ای ندارید.</p>
                @endforelse
                @if($parentUnreadCount > 0)
                    <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button type="submit" class="parent-notification-read-all">خواندن همه اعلان‌ها</button></form>
                @endif
            </div>
        </details>

        <details class="parent-menu parent-profile-menu">
            <summary class="parent-profile-trigger" aria-label="منوی حساب کاربری" title="حساب کاربری">
                <span class="parent-profile-avatar">
                    @if($parentUser?->avatarUrl())
                        <img src="{{ $parentUser->avatarUrl() }}" alt="">
                    @else
                        {{ mb_substr((string) $parentUser?->name, 0, 1) }}
                    @endif
                </span>
                <span class="parent-profile-info">
                    <span class="parent-profile-name">{{ $parentUser?->name }}</span>
                    <span class="parent-profile-sub">اولیا</span>
                </span>
                <svg class="parent-profile-chevron" viewBox="0 0 24 24" width="14" height="14" stroke="currentColor" stroke-width="2.2" fill="none" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <polyline points="6 9 12 15 18 9"></polyline>
                </svg>
            </summary>
            <div class="parent-menu-pop parent-profile-pop">
                @if($parentUser?->hasPermissionTo('profile.update'))
                <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                    @csrf
                    <label class="parent-avatar-upload">
                        <span>تغییر تصویر پروفایل</span>
                        <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                    </label>
                    <button type="submit" class="parent-avatar-submit">بارگذاری تصویر</button>
                </form>
                @endif
                <form method="POST" action="{{ route('logout') }}" class="parent-profile-logout">
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
