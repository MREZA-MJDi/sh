@php
    $t = $teacher ?? auth()->user();
    $nav = [
        ['teacher', 'داشبورد', '🏠'],
        ['teacher/classes', 'کلاس‌ها', '🎬'],
        ['teacher/homework', 'تکالیف', '📝'],
        ['teacher/attendance', 'حضور و غیاب', '✅'],
        ['teacher/live', 'کلاس آنلاین', '🎥'],
        ['teacher/messages', 'پیام اولیا', '💬'],
        ['teacher/consultations', 'درخواست مشاوره', '🗨️'],
        ['teacher/meetings', 'جلسات', '🤝'],
    ];

    // These direct links are visible only when the administrator grants the corresponding teacher permissions.
    if ($t?->hasPermissionTo('homework.manage.assigned') || $t?->hasPermissionTo('homework.view.assigned')) {
        $nav[] = ['admin/homework', 'مدیریت تکالیف و تصحیح', '📝'];
    }
    if ($t?->hasPermissionTo('resources.manage.assigned')) {
        $nav[] = ['admin/resources', 'بارگذاری جزوه و منابع', '📚'];
    }
    if ($t?->hasPermissionTo('videos.manage.assigned')) {
        $nav[] = ['admin/spotplayer', 'بارگذاری فیلم‌ها', '🎞️'];
    }
@endphp
<div class="teacher-sidebar">
    <div class="me">
        <div class="av">{{ mb_substr((string) data_get($t, 'name', ''), 0, 1) }}</div>
        <div>{{ data_get($t, 'name') }}<small>{{ data_get($t, 'subject') }}</small></div>
    </div>
    <nav>
        @foreach ($nav as [$path, $label, $icon])
            @php $active = $path === 'teacher' ? request()->is('teacher') : request()->is($path) || request()->is($path.'/*'); @endphp
            <a href="{{ url($path) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif><span>{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
</div>
