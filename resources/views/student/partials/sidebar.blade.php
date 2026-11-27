@php
    // هر بخش فقط به مسیرهای واقعی پنل دانش‌آموز اشاره می‌کند.
    $groups = [
        [null, [['خانه یادگیری', 'i-home', 'student']]],
        ['یادگیری', [
            ['دوره‌های من', 'i-book', 'student/courses'],
            ['کلاس‌های من', 'i-video', 'student/live-classes'],
            ['ویدیوهای ضبط‌شده', 'i-video', 'student/videos'],
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
    $permissions = [
        'student/courses' => 'lessons.view', 'student/videos' => 'videos.view.enrolled',
        'student/homework' => 'homework.submit', 'student/exams' => 'exams.view.own',
        'student/results' => 'exams.view.own', 'student/resources' => 'resources.view.enrolled',
        'student/messages' => 'messages.view.own', 'student/notes' => 'notes.manage.own',
        'student/profile' => 'profile.update',
    ];
    $groups = array_map(fn ($group) => [$group[0], array_values(array_filter($group[1], fn ($item) => !isset($permissions[$item[2]]) || auth()->user()?->hasPermissionTo($permissions[$item[2]])))], $groups);
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
            @if ($heading && $items)<h6>{{ $heading }}</h6>@endif
            @foreach ($items as [$label, $icon, $path])
                @php $active = $path === 'student' ? request()->is('student') : request()->is($path, $path.'/*'); @endphp
                <a href="{{ url($path) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif>
                    <svg class="i"><use href="#{{ $icon }}"/></svg>{{ $label }}
                </a>
            @endforeach
        @endforeach
    </nav>

    <div class="prem">
        <h4>برنامه کلاس‌ها</h4>
        <a class="btn s" href="{{ route('student.live-classes') }}">مشاهده جلسات</a>
        <form method="POST" action="{{ url('logout') }}">@csrf<button type="submit">خروج</button></form>
    </div>
</aside>
