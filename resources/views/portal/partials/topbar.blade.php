<header class="top">
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

    <h1>@yield('title', 'کارتابل')</h1>

    @if($portalRole === 'parent')
        <label class="kid">
            <span class="sr">انتخاب فرزند</span>
            <select data-child @disabled($children->isEmpty())>
                @forelse($children as $childOption)
                    <option value="{{ $childOption->id }}" @selected($selectedChild?->id === $childOption->id)>
                        {{ $childOption->name }} · {{ $childOption->grade ?? 'پایه ثبت نشده' }}
                    </option>
                @empty
                    <option value="">فرزندی متصل نشده است</option>
                @endforelse
            </select>
        </label>
    @endif

    @can('announcements.view')
        <a class="ib" href="#announcements" aria-label="رفتن به اطلاعیه‌ها">
            <x-icon name="bell"/>
            @if(isset($announcements) && $announcements->isNotEmpty())
                <i>{{ $announcements->count() > 9 ? '۹+' : $announcements->count() }}</i>
            @endif
        </a>
    @endcan

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
