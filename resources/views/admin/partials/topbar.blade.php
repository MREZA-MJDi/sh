@php
    $roleSwitcherItems = [
        ['role' => 'admin', 'label' => 'مدیر', 'description' => 'مدیریت کامل آکادمی', 'route' => 'admin.dashboard', 'symbol' => 'shield'],
        ['role' => 'teacher', 'label' => 'مدرس', 'description' => 'کلاس‌ها، دوره‌ها و دانش‌آموزان', 'route' => 'admin.dashboard', 'symbol' => 'board'],
        ['role' => 'student', 'label' => 'دانش‌آموز', 'description' => 'دوره‌ها، تکالیف و آزمون‌ها', 'route' => 'student.dashboard', 'symbol' => 'cap'],
        ['role' => 'parent', 'label' => 'ولی دانش‌آموز', 'description' => 'پیگیری وضعیت آموزشی فرزند', 'route' => 'guardian.dashboard', 'symbol' => 'family'],
    ];
@endphp
<header class="panel-topbar">
    <button type="button" class="panel-icon-btn panel-mobile-toggle" data-panel-menu-toggle aria-label="باز کردن منو">
        <svg class="admin-icon"><use href="#admin-menu"/></svg>
    </button>

    <div class="panel-topbar-title">
        <h1>@yield('page-title', 'داشبورد')</h1>
        <p>@yield('page-description', 'نمای کلی امروز آکادمی شیخان')</p>
    </div>

    <div class="panel-topbar-actions">
        <label class="panel-global-search">
            <svg class="admin-icon"><use href="#admin-search"/></svg>
            <input type="search" placeholder="جستجو در این صفحه..." data-table-search aria-label="جستجو در این صفحه">
        </label>

        <button type="button" class="panel-role-switcher-trigger" data-role-switcher-open aria-haspopup="dialog" aria-controls="panel-role-switcher">
            <span class="panel-role-switcher-trigger-icon"><svg class="admin-icon"><use href="#admin-user"/></svg></span>
            <span class="panel-role-switcher-trigger-copy"><strong>ورود به پنل‌ها</strong><small>انتخاب نقش کاربری</small></span>
            <svg class="admin-icon panel-role-switcher-chevron"><path d="m7 10 5 5 5-5"/></svg>
        </button>

        <button type="button" class="panel-icon-btn" data-panel-theme-toggle aria-label="تغییر تم" title="تغییر تم">
            <svg class="admin-icon admin-moon"><use href="#admin-moon"/></svg>
            <svg class="admin-icon admin-sun"><use href="#admin-sun"/></svg>
        </button>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="panel-icon-btn" type="submit" aria-label="خروج از حساب" title="خروج">
                <svg class="admin-icon"><use href="#admin-exit"/></svg>
            </button>
        </form>
    </div>
</header>

<dialog class="panel-role-dialog" id="panel-role-switcher" aria-labelledby="panel-role-dialog-title">
    <div class="panel-role-dialog-head">
        <div>
            <span class="panel-role-dialog-eyebrow">SHIKHAN ACADEMY</span>
            <h2 id="panel-role-dialog-title">از کدام پنل می‌خواهید وارد شوید؟</h2>
            <p>نقش موردنظرتان را انتخاب کنید تا به فضای کاری مربوط به آن بروید.</p>
        </div>
        <button type="button" class="panel-role-dialog-close" data-role-switcher-close aria-label="بستن پنجره">
            <svg class="admin-icon"><use href="#admin-close"/></svg>
        </button>
    </div>

    <div class="panel-role-dialog-grid">
        @foreach ($roleSwitcherItems as $roleItem)
            @php($canEnterRole = $adminUser->hasRole($roleItem['role']))
            @if ($canEnterRole)
                <a class="panel-role-card is-available" href="{{ route($roleItem['route']) }}">
            @else
                <div class="panel-role-card is-unavailable" aria-disabled="true">
            @endif
                <span class="panel-role-card-icon role-icon-{{ $roleItem['symbol'] }}">
                    @switch($roleItem['symbol'])
                        @case('shield')
                            <svg class="admin-icon" viewBox="0 0 24 24"><path d="M12 3 20 6v5c0 5-3.5 8.5-8 10-4.5-1.5-8-5-8-10V6z"/><path d="m9 12 2 2 4-4"/></svg>
                            @break
                        @case('board')
                            <svg class="admin-icon" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="13" rx="2"/><path d="M8 21l4-4 4 4M8 9h8M8 13h5"/></svg>
                            @break
                        @case('cap')
                            <svg class="admin-icon" viewBox="0 0 24 24"><path d="m2 9 10-5 10 5-10 5z"/><path d="M6 11v5c3.5 3 8.5 3 12 0v-5M22 9v6"/></svg>
                            @break
                        @case('family')
                            <svg class="admin-icon" viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><circle cx="17" cy="9" r="2.5"/><path d="M2.5 20a6.5 6.5 0 0 1 13 0M15 15a5 5 0 0 1 6.5 4"/></svg>
                            @break
                    @endswitch
                </span>
                <span class="panel-role-card-copy">
                    <strong>{{ $roleItem['label'] }}</strong>
                    <small>{{ $roleItem['description'] }}</small>
                    <em>{{ $canEnterRole ? 'ورود به پنل' : 'این نقش به حساب شما اختصاص ندارد' }}</em>
                </span>
                <svg class="admin-icon panel-role-card-arrow" viewBox="0 0 24 24"><path d="M19 12H5m7 7-7-7 7-7"/></svg>
            @if ($canEnterRole)
                </a>
            @else
                </div>
            @endif
        @endforeach
    </div>
    <p class="panel-role-dialog-footnote">دسترسی هر پنل بر اساس نقش و مجوزهای حساب کاربری شما کنترل می‌شود.</p>
</dialog>
