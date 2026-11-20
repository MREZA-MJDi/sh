@php
  $topbarUser = auth()->user();
  $topbarNotifications = $topbarUser?->unreadNotifications()->latest()->limit(6)->get() ?? collect();
  $unreadNotificationCount = $topbarUser?->unreadNotifications()->count() ?? 0;
  $topbarRoleLabel = $topbarUser?->hasRole('admin') ? 'مدیر سامانه' : ($topbarUser?->hasRole('seo') ? 'کارشناس سئو' : ($topbarUser?->hasRole('teacher') ? 'مدرس' : 'کاربر'));
  $topbarInitial = mb_substr((string) ($topbarUser?->name ?? '؟'), 0, 1);
@endphp
<header class="adm-topbar">
  <x-admin.btn variant="line" size="icon" icon="menu" class="adm-menu-btn" data-menu aria-label="باز کردن منو"/>

  <nav class="adm-crumbs" aria-label="مسیر">
    <a href="{{ url(($topbarUser?->hasRole('seo') && ! $topbarUser?->hasRole('admin')) ? 'admin/seo' : 'admin') }}">{{ ($topbarUser?->hasRole('seo') && ! $topbarUser?->hasRole('admin')) ? 'مرکز مدیریت سئو' : 'پنل مدیریت' }}</a>
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

    <details class="adm-menu adm-notification-menu" data-admin-topbar-menu>
      <summary class="adm-btn adm-btn-line adm-btn-icon" aria-label="اعلان‌ها">
        <x-admin.icon name="bell"/>
        @if($unreadNotificationCount > 0)<i class="adm-dot" aria-label="{{ number_format($unreadNotificationCount) }} اعلان خوانده‌نشده"></i>@endif
      </summary>
      <div class="adm-menu-pop adm-notification-pop" aria-label="اعلان‌های خوانده‌نشده">
        <header class="adm-menu-pop-head">
          <span>اعلان‌های خوانده‌نشده</span>
          <b class="adm-num">{{ number_format($unreadNotificationCount) }}</b>
        </header>
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
          <p class="adm-muted adm-notification-empty">اعلان خوانده‌نشده‌ای ندارید.</p>
        @endforelse
        @if($unreadNotificationCount > 0)
          <form method="POST" action="{{ route('notifications.read-all') }}">
            @csrf @method('PATCH')
            <button type="submit" class="adm-notification-read-all">خواندن همه اعلان‌ها</button>
          </form>
        @endif
      </div>
    </details>

    <details class="adm-menu adm-profile-menu" data-admin-topbar-menu>
      <summary class="adm-avatar adm-profile-trigger" aria-label="پروفایل کاربری" @if($topbarUser?->avatarUrl()) style="overflow:hidden;padding:0" @endif>
        @if($topbarUser?->avatarUrl())
          <img src="{{ $topbarUser->avatarUrl() }}" alt="{{ $topbarUser->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
        @else
          {{ $topbarInitial }}
        @endif
      </summary>
      <div class="adm-menu-pop adm-profile-pop" aria-label="پروفایل کاربری">
        <div class="adm-profile-summary">
          <span class="adm-avatar adm-profile-avatar" aria-hidden="true" @if($topbarUser?->avatarUrl()) style="overflow:hidden;padding:0" @endif>
            @if($topbarUser?->avatarUrl())
              <img src="{{ $topbarUser->avatarUrl() }}" alt="{{ $topbarUser->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            @else
              {{ $topbarInitial }}
            @endif
          </span>
          <div class="adm-profile-identity">
            <b class="adm-profile-name">{{ $topbarUser?->name ?? 'کاربر' }}</b>
            <small>{{ $topbarRoleLabel }}</small>
            @if($topbarUser?->email)<small class="adm-profile-email">{{ $topbarUser->email }}</small>@endif
          </div>
        </div>
        <form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" style="padding:10px 14px;border-bottom:1px solid var(--line)">
          @csrf
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;font-size:12px"><span>تغییر عکس پروفایل</span><input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required style="max-width:150px" onchange="this.form.submit()"></label>
        </form>
        <form method="POST" action="{{ url('logout') }}">
          @csrf
          <button type="submit" class="adm-profile-logout"><x-admin.icon name="logout"/>خروج از حساب</button>
        </form>
      </div>
    </details>
  </div>
</header>
