@php
    $cartCount = count(session('public_cart', []));
    $user = auth()->user();
    $currentDashboard = null;
    if ($user) {
        $currentDashboard = $user->hasRole('student') 
            ? route('student.dashboard') 
            : ($user->hasRole('teacher') 
                ? route('teacher.dashboard') 
                : ($user->hasRole('parent') ? route('guardian.dashboard') : route('admin.dashboard')));
    }
@endphp

<header class="nv">
    <div class="c nvr">
        <button class="ib burger" id="bg" type="button" aria-label="منو">
            <svg class="i"><use href="#menu"/></svg>
        </button>

        <a class="logo" href="{{ route('home') }}" style="margin:0;cursor:pointer">
            <svg class="i"><use href="#cap"/></svg>
            <span>{{ $settings->site_name ?? 'شیخان' }}</span>
        </a>

        <nav class="links" id="lk" aria-label="ناوبری اصلی">
            <a class="{{ request()->routeIs('home') ? 'on' : '' }}" href="{{ route('home') }}">خانه</a>

            <div class="mg">
                <a class="{{ request()->routeIs('public.courses.*') ? 'on' : '' }}" href="{{ route('public.courses.index') }}">
                    دوره‌ها <svg class="i" style="width:1em"><use href="#chev"/></svg>
                </a>
                <div class="card mega">
                    <div>
                        <h5>بر اساس پایه</h5>
                        <a href="{{ route('public.courses.index', ['grade' => 'دهم']) }}">پایه دهم</a>
                        <a href="{{ route('public.courses.index', ['grade' => 'یازدهم']) }}">پایه یازدهم</a>
                        <a href="{{ route('public.courses.index', ['grade' => 'دوازدهم']) }}">پایه دوازدهم</a>
                        <a href="{{ route('public.courses.index', ['grade' => 'کنکور']) }}">پایه سیزدهم (کنکور)</a>
                        <a href="{{ route('public.courses.index') }}"><b style="color:var(--pri)">همه دوره‌ها</b></a>
                    </div>
                    <div>
                        <h5>بر اساس درس</h5>
                        <a href="{{ route('public.courses.index', ['subject' => 'ریاضی']) }}">ریاضی</a>
                        <a href="{{ route('public.courses.index', ['subject' => 'فیزیک']) }}">فیزیک</a>
                        <a href="{{ route('public.courses.index', ['subject' => 'شیمی']) }}">شیمی</a>
                        <a href="{{ route('public.courses.index', ['subject' => 'زیست']) }}">زیست</a>
                    </div>
                    <div class="promo">
                        <b>کوییز مسیر یادگیری</b><br>
                        در ۳۰ ثانیه دوره مناسب خودت را پیدا کن.<br>
                        <a href="{{ route('home') }}#quiz" style="color:#fff;text-decoration:underline">شروع کوییز</a>
                    </div>
                </div>
            </div>

            <a class="{{ request()->routeIs('public.teachers.*') ? 'on' : '' }}" href="{{ route('public.teachers.index') }}">مدرسان</a>
            <a class="{{ request()->routeIs('seo.articles.*') ? 'on' : '' }}" href="{{ route('seo.articles.index') }}">مقالات</a>
            <a class="{{ request()->routeIs('public.contact') ? 'on' : '' }}" href="{{ route('public.contact') }}">تماس با ما</a>
        </nav>

        <div class="act">
            <button class="ib" id="sb" type="button" aria-label="جستجو">
                <svg class="i"><use href="#search"/></svg>
            </button>

            <a class="ib" href="{{ route('public.cart') }}" aria-label="سبد خرید" style="cursor:pointer">
                <svg class="i"><use href="#cartI"/></svg>
                @if($cartCount > 0)
                    <i class="cn">{{ $cartCount }}</i>
                @endif
            </a>

            @guest
                <a class="tl" href="{{ route('login') }}">ورود / ثبت‌نام</a>
                <button class="btn s" id="pb" type="button">پنل من</button>
            @else
                <button class="btn s" id="pb" type="button" style="display:inline-flex;gap:6px;align-items:center">
                    <span>{{ Str::limit($user->name, 14) }}</span>
                    <svg class="i" style="width:0.85em"><use href="#chev"/></svg>
                </button>
            @endguest
        </div>
    </div>

    <!-- نوار جستجوی کشویی -->
    <div class="sbar" id="sbar">
        <div class="c">
            <form action="{{ route('public.courses.index') }}" method="GET" style="display:flex;flex-direction:column;gap:10px">
                <input class="inp" id="hq" name="q" value="{{ request('q') }}" placeholder="جستجوی دوره، درس یا مدرس… (Enter)">
                <div class="chips">
                    <a class="chip" href="{{ route('public.courses.index', ['subject' => 'ریاضی']) }}">ریاضی</a>
                    <a class="chip" href="{{ route('public.courses.index', ['subject' => 'فیزیک']) }}">فیزیک</a>
                    <a class="chip" href="{{ route('public.courses.index', ['grade' => 'کنکور']) }}">کنکور</a>
                    <a class="chip" href="{{ route('public.courses.index', ['subject' => 'زیست']) }}">زیست</a>
                    <a class="chip" href="{{ route('public.courses.index', ['subject' => 'زبان']) }}">زبان</a>
                </div>
            </form>
        </div>
    </div>
</header>

<!-- منوی بازشونده پنل من -->
<div class="card ddm" id="pm">
    @auth
        <div style="padding:10px 12px;border-bottom:1px solid var(--ln);margin-bottom:6px">
            <b style="display:block;font-size:15px;color:var(--ink)">{{ $user->name }}</b>
            <small style="color:var(--mute)">{{ $user->mobile ?? $user->email }}</small>
        </div>
        @if($currentDashboard)
            <a href="{{ $currentDashboard }}" style="background:var(--soft);color:var(--pri);margin-bottom:4px">
                <div class="ic" style="background:var(--pri)">
                    <svg class="i"><use href="#cap"/></svg>
                </div>
                <div>
                    <b>داشبورد اصلی من</b>
                    <small style="color:var(--pri)">ورود به میز کار شما</small>
                </div>
            </a>
        @endif
    @endauth

    <a href="{{ route('student.dashboard') }}">
        <div class="ic" style="background:linear-gradient(135deg,#0d9488,#0f766e)">
            <svg class="i"><use href="#cap"/></svg>
        </div>
        <div>
            <b>پنل دانشجو</b>
            <small>دوره‌ها، تکالیف، آزمون و رشد تحصیلی</small>
        </div>
    </a>

    <a href="{{ route('teacher.dashboard') }}">
        <div class="ic" style="background:linear-gradient(135deg,#f97316,#db2777)">
            <svg class="i"><use href="#pen"/></svg>
        </div>
        <div>
            <b>پنل مدرس</b>
            <small>مدیریت دوره‌ها، دانشجوها و درآمد</small>
        </div>
    </a>

    <a href="{{ route('guardian.dashboard') }}">
        <div class="ic" style="background:linear-gradient(135deg,#2563eb,#7c3aed)">
            <svg class="i"><use href="#user"/></svg>
        </div>
        <div>
            <b>پنل والدین</b>
            <small>گزارش پیشرفت، حضور و پرداخت‌ها</small>
        </div>
    </a>

    <a href="{{ route('admin.dashboard') }}">
        <div class="ic" style="background:linear-gradient(135deg,#0891b2,#2563eb)">
            <svg class="i"><use href="#chart"/></svg>
        </div>
        <div>
            <b>پنل مدیریت</b>
            <small>کاربران، دوره‌ها، مالی و محتوا</small>
        </div>
    </a>

    @auth
        <div style="border-top:1px solid var(--ln);margin-top:6px;padding-top:6px">
            <form action="{{ route('logout') }}" method="POST" style="margin:0">
                @csrf
                <button type="submit" style="width:100%;background:none;border:0;display:flex;align-items:center;gap:12px;padding:10px;border-radius:12px;color:#dc2626;cursor:pointer;font:inherit;font-weight:600">
                    <div class="ic" style="background:#fee2e2;color:#dc2626">
                        <svg class="i"><use href="#logout"/></svg>
                    </div>
                    <span>خروج از حساب</span>
                </button>
            </form>
        </div>
    @endauth
</div>
