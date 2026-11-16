@php
    // هر بخش فقط به مسیرهای واقعی پنل دانش‌آموز اشاره می‌کند.
    $groups = [
        [null, [['خانه یادگیری', 'i-home', 'student']]],
        ['یادگیری', [
            ['دوره‌های من', 'i-book', 'student/courses'],
            ['کلاس‌های من', 'i-video', 'student/live-classes'],
            ['تکالیف', 'i-task', 'student/homework'],
            ['آزمون‌ها', 'i-exam', 'student/exams'],
            ['جزوه‌ها و منابع', 'i-file', 'student/resources'],
        ]],
        ['ارتباط', [['پیام به مدرس‌ها', 'i-msg', 'student/messages']]],
        ['عملکرد و حساب', [
            ['نمرات و عملکرد', 'i-chart', 'student/results'],
            ['حضور و غیاب', 'i-cal', 'student/attendance'],
            ['دستاوردها', 'i-trophy', 'student/achievements'],
            ['یادداشت‌ها', 'i-note', 'student/notes'],
            ['حساب کاربری', 'i-user', 'student/profile'],
        ]],
    ];
    $nextSession = ($upcoming_sessions ?? collect())->first();
@endphp
<aside class="side">
    <div class="me">
        <div class="av"><svg viewBox="0 0 120 120" width="100%" height="100%"><use href="#avatar"/></svg></div>
        <h3>سلام {{ auth()->user()->name ?? '' }}!</h3>
    </div>

    <nav class="nav" aria-label="منوی دانش‌آموز">
        @foreach ($groups as [$heading, $items])
            @if ($heading)<h6>{{ $heading }}</h6>@endif
            @foreach ($items as [$label, $icon, $path])
                @php $active = $path === 'student' ? request()->is('student') : request()->is($path.'*'); @endphp
                <a href="{{ url($path) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif>
                    <svg class="i"><use href="#{{ $icon }}"/></svg>{{ $label }}
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="prem">
        <h4>کلاس بعدی</h4>
        <p>{{ $nextSession?->classroom?->name ?? 'کلاس برنامه‌ریزی‌شده‌ای ندارید' }}</p>
        @if ($nextSession)
            <p>{{ $nextSession->starts_at?->format('Y/m/d H:i') }}</p>
            <form method="POST" action="{{ route('student.class-sessions.join', $nextSession) }}">@csrf<button class="btn s" type="submit">ورود به کلاس</button></form>
        @endif
        <form method="POST" action="{{ url('logout') }}">@csrf<button type="submit">خروج</button></form>
    </div>
</aside>
