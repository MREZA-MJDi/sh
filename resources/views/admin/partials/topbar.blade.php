<header class="panel-topbar">
    <button type="button" class="panel-icon-btn panel-mobile-toggle" data-panel-menu-toggle aria-label="باز کردن منو"><svg class="admin-icon"><use href="#admin-menu"/></svg></button>
    <div class="panel-topbar-title">
        <h1>@yield('page-title', 'داشبورد')</h1>
        <p>@yield('page-description', 'نمای کلی امروز آکادمی شیخان')</p>
    </div>
    <div class="panel-topbar-actions">
        <label class="panel-global-search"><svg class="admin-icon"><use href="#admin-search"/></svg><input type="search" placeholder="جستجو در این صفحه..." data-table-search aria-label="جستجو در این صفحه"></label>
        <button type="button" class="panel-icon-btn" data-panel-theme-toggle aria-label="تغییر تم" title="تغییر تم"><svg class="admin-icon admin-moon"><use href="#admin-moon"/></svg><svg class="admin-icon admin-sun"><use href="#admin-sun"/></svg></button>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="panel-icon-btn" type="submit" aria-label="خروج از حساب" title="خروج"><svg class="admin-icon"><use href="#admin-exit"/></svg></button></form>
    </div>
</header>
