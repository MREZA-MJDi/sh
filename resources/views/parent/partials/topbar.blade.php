<header class="top">
    <div class="logo">آموزشگاه <span>آنلاین</span></div>
    <div class="dots">
        <button type="button" style="background:#4f46e5" data-pal="" aria-label="رنگ‌بندی"></button>
        <button type="button" style="background:#0f9d74" data-pal="g" aria-label="رنگ‌بندی"></button>
        <button type="button" style="background:#e0572f" data-pal="o" aria-label="رنگ‌بندی"></button>
    </div>
    <input class="sr" id="globalSearch" type="search" placeholder="جستجو…" aria-label="جستجو" autocomplete="off">
    <a class="ic" href="{{ url('parent/messages') }}" aria-label="اعلان‌ها" data-fa>🔔<sup style="color:var(--rd)">{{ $notificationsCount ?? '' }}</sup></a>
    <button type="button" class="ic" id="themeToggle" aria-label="تم تیره و روشن">◐</button>
    <details class="ic" style="position:relative;cursor:pointer"><summary aria-label="پروفایل" style="list-style:none;width:38px;height:38px;border-radius:50%;overflow:hidden;display:grid;place-items:center;background:var(--pri);color:#fff">
      @if(auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover">@else{{ mb_substr(auth()->user()->name,0,1) }}@endif
    </summary><form method="POST" action="{{ route('profile.avatar.update') }}" enctype="multipart/form-data" style="position:absolute;z-index:20;top:44px;left:0;background:var(--card,#fff);padding:12px;border:1px solid var(--ln);border-radius:12px;min-width:190px">
      @csrf<label style="font-size:12px;display:grid;gap:8px">تغییر عکس پروفایل<input type="file" name="avatar" accept="image/jpeg,image/png,image/webp" required onchange="this.form.submit()"></label>
    </form></details>
    <form method="POST" action="{{ route('logout') }}" class="logout-form">
        @csrf
        <button type="submit" class="ic logout-btn">خروج از حساب</button>
    </form>
</header>
