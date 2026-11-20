@php
    $parentUser = auth()->user();
    $parentNotifications = $parentUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
    $parentUnreadCount = $parentUser?->unreadNotifications()->count() ?? 0;
@endphp
<header class="top parent-topbar" data-parent-topbar>
    <button type="button" class="ic parent-nav-toggle" id="parentNavToggle" aria-controls="parentSidebar" aria-expanded="false" aria-label="باز کردن منوی اصلی">
        <span aria-hidden="true">☰</span><span>منو</span>
    </button>
    <a class="logo" href="{{ route('guardian.dashboard') }}">آموزشگاه <span>آنلاین</span></a>
    <div class="dots parent-palette" role="group" aria-label="انتخاب رنگ پنل">
        <button type="button" data-pal="" aria-label="رنگ‌بندی بنفش" aria-pressed="true" title="بنفش"></button>
        <button type="button" data-pal="g" aria-label="رنگ‌بندی سبز" aria-pressed="false" title="سبز"></button>
        <button type="button" data-pal="o" aria-label="رنگ‌بندی نارنجی" aria-pressed="false" title="نارنجی"></button>
    </div>
    <label class="parent-global-search">
        <span class="sr-only">جست‌وجو در همین صفحه</span>
        <input class="sr" id="globalSearch" type="search" placeholder="جستجو در همین صفحه…" autocomplete="off" aria-controls="parentSearchResults" aria-describedby="parentSearchHint">
        <span id="parentSearchHint" class="sr-only">جست‌وجو فقط در اطلاعات بارگذاری‌شده همین صفحه انجام می‌شود.</span>
        <button type="button" id="parentSearchClear" class="parent-search-clear" aria-label="پاک کردن جست‌وجو" hidden>×</button>
    </label>
    <span id="parentSearchResults" class="sr-only" role="status" aria-live="polite"></span>
    <details class="parent-menu parent-notification-menu">
        <summary class="ic" aria-label="اعلان‌ها، {{ $parentUnreadCount }} اعلان خوانده‌نشده" title="اعلان‌ها">
            <span aria-hidden="true">🔔</span>
            @if($parentUnreadCount > 0)<sup>{{ $parentUnreadCount > 99 ? '۹۹+' : $parentUnreadCount }}</sup>@endif
        </summary>
        <div class="parent-menu-pop parent-notification-pop">
            <div class="parent-menu-heading"><strong>اعلان‌های خوانده‌نشده</strong><span>{{ $parentUnreadCount }}</span></div>
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
    <button type="button" class="ic" id="themeToggle" aria-label="فعال‌سازی تم تیره" aria-pressed="false" title="تغییر پوسته">◐</button>
    <details class="parent-menu parent-profile-menu">
        <summary class="parent-profile-trigger" aria-label="منوی حساب کاربری" title="حساب کاربری">
            <span class="parent-profile-avatar">
                @if($parentUser?->avatarUrl())<img src="{{ $parentUser->avatarUrl() }}" alt="">@else{{ mb_substr((string) $parentUser?->name, 0, 1) }}@endif
            </span>
            <span class="parent-profile-name">{{ $parentUser?->name }}</span><span aria-hidden="true">⌄</span>
        </summary>
        <div class="parent-menu-pop parent-profile-pop">
            @if($parentUser?->hasPermissionTo('profile.update'))
            <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                @csrf
                <label class="parent-avatar-upload">تغییر تصویر پروفایل<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required></label>
                <button type="submit" class="parent-avatar-submit">بارگذاری تصویر</button>
            </form>
            @endif
            <form method="POST" action="{{ route('logout') }}" class="parent-profile-logout">@csrf<button type="submit">خروج از حساب</button></form>
        </div>
    </details>
</header>