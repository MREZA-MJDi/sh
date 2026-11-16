@php
    $t = $teacher ?? auth()->user();
    $nav = [
        ['teacher/dashboard',  'داشبورد',      '🏠'],
        ['teacher/classes',    'کلاس‌ها',      '🎬'],
        ['teacher/homework',   'تکالیف',       '📝'],
        ['teacher/attendance', 'حضور و غیاب',  '✅'],
        ['teacher/live',       'کلاس آنلاین',  '🎥'],
        ['teacher/messages',   'پیام اولیا',   '💬'],
        ['teacher/meetings',   'جلسات',        '🤝'],
    ];
@endphp
<div>
    <div class="me">
        <div class="av">{{ mb_substr((string) data_get($t, 'name', ''), 0, 1) }}</div>
        <div>{{ data_get($t, 'name') }}<small>{{ data_get($t, 'subject') }}</small></div>
    </div>
    <nav>
        @foreach ($nav as [$path, $label, $icon])
            <a href="{{ url($path) }}" class="{{ request()->is($path.'*') ? 'on' : '' }}" @if(request()->is($path.'*')) aria-current="page" @endif><span>{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
</div>
