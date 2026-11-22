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
            ['کتابخانه و خریدها', 'i-cart', 'student/library'],
        ]],
        ['ارتباط', [
            ['پیام به مدرس‌ها', 'i-msg', 'student/messages'],
            ['جلسات با اولیا', 'i-cal', 'student/meetings'],
        ]],
        ['عملکرد و حساب', array_values(array_filter([
            ['نمرات و عملکرد', 'i-chart', 'student/results'],
            auth()->user()?->hasPermissionTo('attendance.view.own')
                ? ['حضور و غیاب', 'i-cal', 'student/attendance']
                : null,
            ['دستاوردها', 'i-trophy', 'student/achievements'],
            ['یادداشت‌ها', 'i-note', 'student/notes'],
            ['حساب کاربری', 'i-user', 'student/profile'],
        ]))],
    ];
    $nextSession = ($upcoming_sessions ?? collect())->first();
@endphp
<aside class="side">
    <div class="me">
        <div class="av">
            @if(auth()->user()?->avatarUrl())
                <img src="{{ auth()->user()->avatarUrl() }}" alt="{{ auth()->user()->name }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            @else
                <svg viewBox="0 0 120 120" width="100%" height="100%"><use href="#avatar"/></svg>
            @endif
        </div>
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
        <p>{{ $nextSession?->title ?: ($nextSession?->classroom?->name ?? 'کلاس برنامه‌ریزی‌شده‌ای ندارید') }}</p>
        @if ($nextSession)
            <p>{{ $nextSession->starts_at?->format('Y/m/d H:i') }}</p>
            @if ($nextSession->session_type === 'online')
                @php
                    $nextJoinReady = filled($nextSession->meeting_url)
                        || ($nextSession->meeting_provider === 'skyroom'
                            && filled($nextSession->classroom?->skyroom_room_id)
                            && $nextSession->classroom?->skyroom_status === 'ready');
                    $joinWindowOpen = $nextSession->starts_at && $nextSession->ends_at
                        && $nextSession->starts_at->lte(now()->addMinutes(15))
                        && $nextSession->ends_at->gte(now());
                @endphp
                @if($nextJoinReady && $joinWindowOpen)
                    <form method="POST" action="{{ route('student.class-sessions.join', $nextSession) }}" target="_blank" rel="noopener">@csrf<button class="btn s" type="submit">ورود به کلاس آنلاین</button></form>
                @elseif(! $nextJoinReady)
                    <span class="tag">{{ $nextSession->meeting_provider === 'jitsi' ? 'لینک Jitsi هنوز ثبت نشده است' : 'اتصال Skyroom در انتظار فعال‌سازی' }}</span>
                @else
                    <span class="tag">ورود از ۱۵ دقیقه پیش از شروع</span>
                @endif
            @else
                <span class="tag">جلسه حضوری</span>
            @endif
        @endif
        <form method="POST" action="{{ url('logout') }}">@csrf<button type="submit">خروج</button></form>
    </div>
</aside>
