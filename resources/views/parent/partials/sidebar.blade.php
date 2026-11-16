@php
    $nav = [
        ['parent/dashboard',  'داشبورد',       '🏠'],
        ['parent/attendance', 'حضور و غیاب',   '✅'],
        ['parent/exams',      'آزمون‌ها',      '📊'],
        ['parent/homework',   'تکالیف',        '📝'],
        ['parent/messages',   'پیام به مدرس',  '💬'],
        ['parent/meetings',   'جلسات مشاوره',  '🤝'],
    ];
@endphp
<div>
    <div class="me" data-fa>
        <div class="av">و</div>
        <div>ولی {{ data_get($kid ?? null, 'name') }}<small>{{ count($kids ?? []) }} فرزند</small></div>
    </div>
    <nav>
        @foreach ($nav as [$path, $label, $icon])
            <a href="{{ url($path) }}" class="{{ request()->is($path.'*') ? 'on' : '' }}" @if(request()->is($path.'*')) aria-current="page" @endif><span>{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
</div>
