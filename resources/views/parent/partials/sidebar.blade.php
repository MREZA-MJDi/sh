@php
    $guardian = auth()->user();
@endphp

<aside class="side" id="side" aria-label="منوی اولیاء">
    <div class="me">
        <div class="av">
            <svg viewBox="0 0 120 120" width="100%" height="100%" aria-hidden="true">
                <use href="#avatar"></use>
            </svg>
        </div>
        <h3>{{ $guardian?->name ?? 'ولی دانش‌آموز' }}</h3>
        <small>کارتابل اولیاء</small>
    </div>

    <nav class="nav" aria-label="بخش‌های کارتابل اولیاء">
        <a href="#overview" data-dashboard-nav-target="overview" class="on" aria-current="page" data-nav-link>
            <x-icon name="home"/> خانه
        </a>

        <h6>وضعیت فرزند</h6>
        <a href="#classes" data-dashboard-nav-target="classes" data-nav-link><x-icon name="book"/> کلاس‌ها و دوره‌ها</a>
        <a href="#homework" data-dashboard-nav-target="homework" data-nav-link><x-icon name="task"/> تکالیف</a>
        <a href="#results" data-dashboard-nav-target="results" data-nav-link><x-icon name="exam"/> نتایج آزمون‌ها</a>
        <a href="#attendance" data-dashboard-nav-target="attendance" data-nav-link><x-icon name="cal"/> حضور و غیاب</a>
        <a href="#resources" data-dashboard-nav-target="resources" data-nav-link><x-icon name="file"/> منابع آموزشی</a>

        <h6>ارتباط با آکادمی</h6>
        <a href="{{ route('guardian.consultations.index') }}"><x-icon name="phone"/> درخواست مشاوره</a>
        <a href="{{ route('guardian.messages.index') }}"><x-icon name="msg"/> پیام‌ها</a>

        <h6>اطلاع‌رسانی</h6>
        <a href="#announcements" data-dashboard-nav-target="announcements" data-nav-link><x-icon name="bell"/> اطلاعیه‌ها</a>
    </nav>

    <div class="prem">
        <h4>پیگیری آموزش فرزند</h4>
        <p>گزارش کلاس‌ها، تکالیف و نتایج فرزند متصل به حساب را مشاهده کنید.</p>
        <a class="btn s" href="{{ route('guardian.consultations.create') }}">درخواست جلسه</a>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout" type="submit">خروج از حساب</button>
        </form>
    </div>
</aside>
