<header class="top">
    <h1>@yield('title', 'مرکز یادگیری')</h1>
    <label class="search">
        <svg class="i"><use href="#i-search"/></svg>
        <input type="search" id="globalSearch" placeholder="جست‌وجوی دوره، آزمون، تکلیف..." autocomplete="off">
    </label>
    <button type="button" class="ib" id="themeToggle" aria-label="تغییر حالت تیره/روشن"><svg class="i"><use href="#i-moon"/></svg></button>
    <a class="ib pr" href="{{ url('student/profile') }}" aria-label="حساب کاربری"><svg class="i"><use href="#i-user"/></svg></a>
</header>
