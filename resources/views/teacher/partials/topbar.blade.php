@php
    $teacherNotifications = auth()->user()?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
    $teacherUnreadCount = auth()->user()?->unreadNotifications()->count() ?? 0;
@endphp
<header class="top">
    <div class="logo">آکادمی <span>شیخان</span></div>
    <div class="dots">
        <button type="button" style="background:#4f46e5" data-pal="" aria-label="رنگ‌بندی پیش‌فرض"></button>
        <button type="button" style="background:#0f9d74" data-pal="g" aria-label="رنگ‌بندی سبز"></button>
        <button type="button" style="background:#e0572f" data-pal="o" aria-label="رنگ‌بندی نارنجی"></button>
    </div>
    <input class="sr" id="globalSearch" type="search" placeholder="جستجو در همین صفحه…" aria-label="جستجو در همین صفحه" autocomplete="off">
    <details class="notification-menu">
        <summary class="ic" aria-label="اعلان‌ها">🔔 @if($teacherUnreadCount > 0)<sup>{{ $teacherUnreadCount }}</sup>@endif</summary>
        <div class="notification-pop">
            <strong>اعلان‌های خوانده‌نشده</strong>
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
                <p>اعلان خوانده‌نشده‌ای ندارید.</p>
            @endforelse
            @if($teacherUnreadCount > 0)
                <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button type="submit" class="notification-read-all">خواندن همه</button></form>
            @endif
        </div>
    </details>
    <button type="button" class="ic" id="themeToggle" aria-label="تم تیره و روشن">◐</button>
    <details class="ic" style="position:relative;cursor:pointer"><summary aria-label="پروفایل" style="list-style:none;width:38px;height:38px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--pri);color:#fff">
      @if(auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover">@else{{ mb_substr(auth()->user()->name,0,1) }}@endif
    </summary><form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" style="position:absolute;z-index:20;top:44px;left:0;background:var(--card,#fff);padding:12px;border:1px solid var(--ln);border-radius:12px;min-width:190px">
      @csrf<label style="font-size:12px;display:grid;gap:8px">تغییر عکس پروفایل<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required onchange="this.form.submit()"></label>
    </form></details>
    <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="logout-btn"><span aria-hidden="true">↪</span> خروج</button>
    </form>
</header>
