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
</header>
