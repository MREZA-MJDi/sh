<header class="top">
    @php($announcements = $announcements ?? collect())
    <button
        type="button"
        class="ib menu-btn"
        data-menu
        aria-controls="side"
        aria-expanded="false"
        aria-label="باز کردن منو"
    >
        <x-icon name="menu"/>
    </button>

    <h1>@yield('title', 'کارتابل اولیاء')</h1>

    @if(isset($children) && $children->isNotEmpty())
        <label class="kid">
            <span class="sr">انتخاب فرزند</span>
            <select data-child aria-label="انتخاب فرزند">
                @foreach($children as $childOption)
                    <option
                        value="{{ $childOption->id }}"
                        @selected($selected_child?->id === $childOption->id)
                    >
                        {{ $childOption->name }} · {{ $childOption->grade ?? 'پایه ثبت نشده' }}
                    </option>
                @endforeach
            </select>
        </label>
    @endif

    <a class="ib" href="#announcements" aria-label="اطلاعیه‌ها">
        <x-icon name="bell"/>
        @if($announcements->isNotEmpty())
            <i>{{ $announcements->count() > 9 ? '۹+' : \App\Support\PersianDate::toPersianDigits($announcements->count()) }}</i>
        @endif
    </a>

    <button
        type="button"
        class="ib"
        data-theme-toggle
        aria-label="تغییر پوسته‌ی روشن و تیره"
        aria-pressed="false"
    >
        <x-icon name="moon"/>
    </button>

    <a class="ib pr" href="#overview" aria-label="حساب کاربری">
        <x-icon name="user"/>
    </a>
</header>
