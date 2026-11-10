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

    <h1>@yield('title', 'مرکز یادگیری')</h1>

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

    <a class="ib pr" href="#overview" aria-label="اطلاعات حساب کاربری">
        <x-icon name="user"/>
    </a>
</header>
