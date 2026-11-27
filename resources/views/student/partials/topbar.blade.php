<header class="top">
    @hasSection('hide-topbar-title')<div class="top-spacer" aria-hidden="true"></div>@else<h1>@yield('title', 'مرکز یادگیری')</h1>@endif
    <label class="search">
        <svg class="i"><use href="#i-search"/></svg>
        <input type="search" id="globalSearch" aria-label="جست‌وجو در همین صفحه" placeholder="جست‌وجو در همین صفحه…" autocomplete="off">
    </label>
    <button type="button" class="ib" id="themeToggle" aria-label="تغییر حالت تیره/روشن"><svg class="i"><use href="#i-moon"/></svg></button>
    <a class="ib pr" href="{{ url('student/profile') }}" aria-label="حساب کاربری" style="overflow:hidden;padding:0;width:38px;height:38px;border-radius:50%;display:grid;place-items:center">
        @if(auth()->user()->avatarUrl())<img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover">@else<svg class="i"><use href="#i-user"/></svg>@endif
    </a>
</header>
