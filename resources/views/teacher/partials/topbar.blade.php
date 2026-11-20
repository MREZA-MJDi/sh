@php
    $teacherUser = auth()->user();
    $teacherNotifications = $teacherUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
    $teacherUnreadCount = $teacherUser?->unreadNotifications()->count() ?? 0;
@endphp
<header class="top" data-teacher-topbar>
    <button type="button" class="ic teacher-nav-toggle" id="teacherNavToggle" aria-controls="teacherSidebar" aria-expanded="false" aria-label="باز کردن منوی اصلی">
        <span aria-hidden="true">☰</span><span class="teacher-nav-toggle-label">منو</span>
    </button>
    <a class="logo" href="{{ route('teacher.dashboard') }}" aria-label="داشبورد مدرس">آکادمی <span>شیخان</span></a>

    <div class="dots teacher-palette" role="group" aria-label="انتخاب رنگ پنل">
        <button type="button" data-pal="" aria-label="رنگ‌بندی بنفش" aria-pressed="true" title="بنفش"></button>
        <button type="button" data-pal="g" aria-label="رنگ‌بندی سبز" aria-pressed="false" title="سبز"></button>
        <button type="button" data-pal="o" aria-label="رنگ‌بندی نارنجی" aria-pressed="false" title="نارنجی"></button>
    </div>

    <label class="teacher-global-search">
        <span class="sr-only">جست‌وجو در محتوای همین صفحه</span>
        <input class="sr" id="globalSearch" type="search" placeholder="جست‌وجوی سریع در پنل…" autocomplete="off" aria-controls="teacherSearchResults" aria-describedby="teacherSearchHint">
        <span id="teacherSearchHint" class="sr-only">نتایج جست‌وجو فقط در اطلاعات بارگذاری‌شده همین صفحه بررسی می‌شوند.</span>
        <button type="button" class="teacher-search-clear" id="teacherSearchClear" aria-label="پاک کردن جست‌وجو" hidden>×</button>
    </label>
    <span id="teacherSearchResults" class="sr-only" role="status" aria-live="polite"></span><div class="teacher-search-links" id="teacherSearchLinks" hidden><strong>رفتن به بخش</strong>@if($teacherUser?->hasPermissionTo('classes.view.assigned'))<a data-search-route href="{{ route('teacher.classes') }}">کلاس‌ها و دوره‌ها</a><a data-search-route href="{{ route('teacher.live') }}">کلاس آنلاین</a><a data-search-route href="{{ route('teacher.meetings') }}">جلسات</a>@endif @if($teacherUser?->hasPermissionTo('homework.view.assigned'))<a data-search-route href="{{ route('teacher.homework') }}">تکالیف</a>@endif @if($teacherUser?->hasPermissionTo('attendance.view.assigned'))<a data-search-route href="{{ route('teacher.attendance') }}">حضور و غیاب</a>@endif @if($teacherUser?->hasPermissionTo('messages.view.assigned'))<a data-search-route href="{{ route('teacher.messages.index') }}">پیام‌ها</a>@endif @if($teacherUser?->hasPermissionTo('resources.manage.assigned'))<a data-search-route href="{{ route('teacher.resources') }}">منابع آموزشی</a>@endif @if($teacherUser?->hasPermissionTo('videos.manage.assigned'))<a data-search-route href="{{ route('teacher.videos') }}">ویدئوها</a>@endif<a data-search-route href="{{ route('teacher.profile.edit') }}">پروفایل</a></div>

    <details class="notification-menu teacher-menu">
        <summary class="ic" aria-label="اعلان‌ها، {{ $teacherUnreadCount }} اعلان خوانده‌نشده" title="اعلان‌ها">
            <span aria-hidden="true">🔔</span>
            @if($teacherUnreadCount > 0)<sup>{{ $teacherUnreadCount > 99 ? '۹۹+' : $teacherUnreadCount }}</sup>@endif
        </summary>
        <div class="notification-pop" aria-label="فهرست اعلان‌ها">
            <div class="teacher-menu-heading"><strong>اعلان‌های خوانده‌نشده</strong><span>{{ $teacherUnreadCount }}</span></div>
            @forelse($teacherNotifications as $notification)
                <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
                    @csrf @method('PATCH')
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
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button type="submit" class="notification-read-all">خواندن همه اعلان‌ها</button></form>
            @endif
        </div>
    </details>

    <button type="button" class="ic" id="themeToggle" aria-label="فعال‌سازی تم تیره" aria-pressed="false" title="تغییر پوسته">◐</button>

    <details class="teacher-profile-menu teacher-menu">
        <summary class="teacher-profile-trigger" aria-label="منوی حساب کاربری" title="حساب کاربری">
            <span class="teacher-profile-avatar">
                @if($teacherUser?->avatarUrl())
                    <img src="{{ $teacherUser->avatarUrl() }}" alt="">
                @else
                    {{ mb_substr((string) $teacherUser?->name, 0, 1) }}
                @endif
            </span>
            <span class="teacher-profile-name">{{ $teacherUser?->name }}</span>
            <span aria-hidden="true">⌄</span>
        </summary>
        <div class="teacher-profile-pop">
            <a href="{{ route('teacher.profile.edit') }}">ویرایش پروفایل</a>
            <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data">
                @csrf
                <label class="teacher-avatar-upload">تغییر تصویر پروفایل
                    <input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                </label>
                <button type="submit" class="teacher-avatar-submit">بارگذاری تصویر</button>
            </form>
            <form method="POST" action="{{ route('logout') }}" class="teacher-profile-logout">@csrf<button type="submit">خروج از حساب</button></form>
        </div>
    </details>
</header>