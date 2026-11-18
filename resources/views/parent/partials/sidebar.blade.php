@php
    $nav = [
        ['parent',            'داشبورد',       '🏠'],
        ['parent/attendance', 'حضور و غیاب',   '✅'],
        ['parent/exams',      'آزمون‌ها',      '📊'],
        ['parent/homework',   'تکالیف',        '📝'],
        ['parent/resources',  'منابع آموزشی', '📚'],
        ['parent/messages',   'پیام به مدرس',  '💬'],
        ['parent/meetings',   'جلسات مشاوره',  '🤝'],
    ];
@endphp
<div>
    <div class="me" data-fa>
        <div class="av">{{ mb_substr((string) auth()->user()->name, 0, 1) }}</div>
        <div>{{ auth()->user()->name }}<small>{{ count($kids ?? []) }} فرزند</small></div>
    </div>
    <nav>
        @foreach ($nav as [$path, $label, $icon])
            <a href="{{ url($path) }}" class="{{ request()->is($path) || ($path !== 'parent' && request()->is($path.'/*')) ? 'on' : '' }}" @if(request()->is($path) || ($path !== 'parent' && request()->is($path.'/*'))) aria-current="page" @endif><span>{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
</div>
