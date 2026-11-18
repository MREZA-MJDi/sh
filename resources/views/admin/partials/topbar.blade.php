@php
  $topbarUser = auth()->user();
  $topbarNotifications = $topbarUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
  $unreadNotificationCount = $topbarUser?->unreadNotifications()->count() ?? 0;
  $topbarRoleLabel = $topbarUser?->hasRole('seo') ? 'کارشناس سئو' : ($topbarUser?->hasRole('teacher') ? 'مدرس' : 'مدیر سامانه');
@endphp
<header class="adm-topbar">
  <x-admin.btn variant="line" size="icon" icon="menu" class="adm-menu-btn" data-menu aria-label="باز کردن منو"/>

  <nav class="adm-crumbs" aria-label="مسیر">
    <a href="{{ url($topbarUser?->hasRole('seo') ? 'admin/seo' : 'admin') }}">{{ $topbarUser?->hasRole('seo') ? 'مرکز مدیریت سئو' : 'پنل مدیریت' }}</a>
    @if ($activeGroup !== null)
      <x-admin.icon name="chev"/><span>{{ $nav['groups'][$activeGroup]['label'] }}</span>
      <x-admin.icon name="chev"/><b>{{ $nav['groups'][$activeGroup]['items'][$activeItem]['label'] }}</b>
    @endif
  </nav>

  <div class="adm-topbar-tools">
    <label class="adm-search-field">
      <x-admin.icon name="search"/>
      <input class="adm-input" type="search" placeholder="جستجو در همین صفحه…" aria-label="جستجو در همین صفحه" data-admin-global-search>
    </label>

    <x-admin.btn variant="line" size="icon" icon="moon" data-theme-toggle aria-label="تغییر تم"/>

    <details class="adm-menu">
      <summary class="adm-btn adm-btn-line adm-btn-icon" aria-label="اعلان‌ها" style="position:relative">
        <x-admin.icon name="bell"/>
        @if($unreadNotificationCount > 0)<i class="adm-dot" aria-label="{{ number_format($unreadNotificationCount) }} اعلان خوانده‌نشده"></i>@endif
      </summary>
      <div class="adm-menu-pop adm-notification-pop">
        <header>اعلان‌های خوانده‌نشده <b class="adm-num">{{ number_format($unreadNotificationCount) }}</b></header>
        @forelse($topbarNotifications as $notification)
          <form method="POST" action="{{ route('notifications.read', $notification->id) }}">
            @csrf @method('PATCH')
            <button type="submit" class="adm-notification-item">
              <b>{{ data_get($notification->data, 'title', 'اعلان جدید') }}</b>
              <small>{{ data_get($notification->data, 'message', 'اعلان جدیدی برای شما ثبت شده است.') }}</small>
              <small>{{ $notification->created_at?->diffForHumans() }}</small>
            </button>
          </form>
        @empty
          <p class="adm-muted">اعلان خوانده‌نشده‌ای ندارید.</p>
        @endforelse
        @if($unreadNotificationCount > 0)
          <form method="POST" action="{{ route('notifications.read-all') }}">@csrf @method('PATCH')<button type="submit" class="adm-notification-read-all">خواندن همه اعلان‌ها</button></form>
        @endif
      </div>
    </details>

    <details class="adm-menu">
      <summary class="adm-avatar" aria-label="حساب کاربری" style="cursor:pointer">{{ mb_substr((string) ($topbarUser?->name ?? '؟'), 0, 1) }}</summary>
      <div class="adm-menu-pop" style="width:220px">
        <header>{{ $topbarRoleLabel }} · {{ $topbarUser?->name }}</header>
        <form method="POST" action="{{ url('logout') }}">@csrf<button type="submit"><x-admin.icon name="logout"/>خروج از حساب</button></form>
      </div>
    </details>
  </div>
</header>
