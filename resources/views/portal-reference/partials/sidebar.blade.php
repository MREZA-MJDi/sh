@php
    $isParent = $portalRole === 'parent';
    $user = auth()->user();
    $links = $isParent ? [
        ['خانه', 'p-home', 'home'],
        ['اخبار مهم', 'p-news', 'bell'],
        ['وضعیت فرزند', null, null],
        ['پیشرفت دوره‌ها و کلاس‌ها', 'p-progress', 'book'],
        ['تکالیف و نتایج بررسی', 'p-hw', 'task'],
        ['نتایج آزمون‌ها', 'p-exams', 'exam'],
        ['حضور و غیاب', 'p-att', 'cal'],
        ['منابع و محصولات', 'p-res', 'file'],
        ['ارتباط', null, null],
        ['درخواست مشاوره', 'p-consult', 'phone'],
        ['ارسال پیام', 'p-msg', 'msg'],
    ] : [
        ['خانه', 's-home', 'home'],
        ['اخبار مهم', 's-news', 'bell'],
        ['یادگیری', null, null],
        ['کلاس‌های من', 's-classes', 'book'],
        ['ویدیوهای ضبط‌شده', 's-videos', 'video'],
        ['تکالیف', 's-hw', 'task'],
        ['آزمون‌ها', 's-exams', 'exam'],
        ['جزوات و منابع', 's-res', 'file'],
        ['کتاب‌ها و محصولات', 's-shop', 'shop'],
        ['ارتباط', null, null],
        ['پیام‌ها', 's-msg', 'msg'],
        ['عملکرد و حساب', null, null],
        ['نتایج', 's-results', 'chart'],
        ['حضور و غیاب', 's-att', 'cal'],
        ['یادداشت‌ها', 's-notes', 'note'],
        ['پروفایل', 's-profile', 'user'],
    ];
@endphp
<aside class="side" id="side" aria-label="{{ $isParent ? 'کارتابل اولیاء' : 'کارتابل دانش‌آموز' }}">
    <div class="me">
        <div class="av"><svg viewBox="0 0 120 120" width="100%" height="100%" aria-hidden="true"><use href="#avatar"></use></svg></div>
        <b id="who">{{ $isParent ? $user->name : $user->name }}</b><br>
        <small id="whoSub">{{ $isParent ? 'ولی دانش‌آموز' : 'دانش‌آموز' }}@if(!$isParent && $user->grade) · {{ $user->grade }}@endif</small>
    </div>
    <nav id="nav{{ $isParent ? 'P' : 'S' }}" aria-label="{{ $isParent ? 'منوی اولیاء' : 'منوی دانش‌آموز' }}">
        @foreach($links as [$label, $target, $icon])
            @if($target === null)
                <h6>{{ $label }}</h6>
            @else
                <a href="#{{ $target }}" data-nav-link data-dashboard-nav-target="{{ $target }}">
                    <x-icon :name="$icon"/> {{ $label }}
                </a>
            @endif
        @endforeach
    </nav>
    <div class="prem">
        <h4>{{ $isParent ? 'پیگیری وضعیت فرزند' : 'مسیر یادگیری' }}</h4>
        <p>{{ $isParent ? 'گزارش آموزشی فرزند متصل به حساب را دنبال کنید.' : 'کلاس‌ها، تکالیف و نتایج آموزشی خودت را پیگیری کن.' }}</p>
        @if($isParent)
            <a class="btn s" href="{{ route('guardian.consultations.create', request()->query()) }}">درخواست جلسه</a>
        @endif
        <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">خروج از حساب</button></form>
    </div>
</aside>
