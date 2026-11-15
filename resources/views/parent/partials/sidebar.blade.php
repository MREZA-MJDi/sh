@php($guardian = auth()->user())
<aside class="side" id="side" aria-label="منوی پنل اولیا">
    <div class="me">
        <div class="av">{{ mb_substr($guardian?->name ?? 'و', 0, 1) }}</div>
        <div><strong>{{ $guardian?->name ?? 'ولی دانش‌آموز' }}</strong><small>پنل اولیا</small></div>
    </div>
    <nav class="nav" aria-label="بخش‌های پنل اولیا">
        @if(request()->routeIs('guardian.dashboard'))
            <a href="#overview" class="on" aria-current="page" data-nav-link data-page="home"><span>🏠</span>داشبورد</a>
            <a href="#attendance" data-nav-link data-page="attendance"><span>✅</span>حضور و غیاب</a>
            <a href="#exams" data-nav-link data-page="exams"><span>📊</span>آزمون‌ها</a>
            <a href="#homework" data-nav-link data-page="homework"><span>📝</span>تکالیف</a>
            <a href="#messages" data-nav-link data-page="messages"><span>💬</span>پیام به مدرس</a>
            <a href="#meetings" data-nav-link data-page="meetings"><span>🤝</span>جلسات مشاوره</a>
        @else
            <a href="{{ route('guardian.dashboard') }}"><span>🏠</span>داشبورد</a>
            <a href="{{ route('guardian.dashboard') }}#attendance"><span>✅</span>حضور و غیاب</a>
            <a href="{{ route('guardian.dashboard') }}#exams"><span>📊</span>آزمون‌ها</a>
            <a href="{{ route('guardian.dashboard') }}#homework"><span>📝</span>تکالیف</a>
            <a href="{{ route('guardian.messages.index') }}"><span>💬</span>پیام به مدرس</a>
            <a href="{{ route('guardian.meetings.index') }}"><span>🤝</span>جلسات مشاوره</a>
        @endif
    </nav>
    <div class="prem">
        <h4>ارتباط با آکادمی</h4>
        <p>برای پیگیری وضعیت آموزشی فرزند یا هماهنگی جلسه با آموزشگاه اقدام کنید.</p>
        <a class="b" href="{{ route('guardian.meetings.index') }}">درخواست جلسه</a>
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">خروج از حساب</button></form>
    </div>
</aside>
