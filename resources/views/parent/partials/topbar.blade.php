<header class="top">
    <div class="logo">آکادمی <span>شیخان</span></div>
    <div class="dots" aria-label="رنگ‌بندی پنل">
        <button type="button" data-palette="i" style="background:#4f46e5" aria-label="بنفش"></button>
        <button type="button" data-palette="o" style="background:#e0572f" aria-label="نارنجی"></button>
    </div>
    @if(isset($children) && $children->isNotEmpty())
        <label class="kid"><span class="sr">انتخاب فرزند</span><select data-child aria-label="انتخاب فرزند">
            @foreach($children as $childOption)
                <option value="{{ $childOption->id }}" @selected(($selected_child ?? null)?->id === $childOption->id)>{{ $childOption->name }} · {{ $childOption->grade ?? 'پایه ثبت نشده' }}</option>
            @endforeach
        </select></label>
    @endif
    <input class="sr" data-panel-search type="search" placeholder="جستجو در گزارش…" aria-label="جستجو در گزارش">
    <a class="ic" href="#announcements" aria-label="اطلاعیه‌ها">🔔</a>
    <button class="ic" type="button" data-theme-toggle aria-label="تم تیره و روشن" aria-pressed="false">◐</button>
</header>
