@php
    $t = $teacher ?? auth()->user();
    $nav = [['teacher', 'داشبورد', '🏠']];
    if ($t?->hasPermissionTo('classes.view.assigned')) {
        $nav[] = ['teacher/classes', 'کلاس‌ها', '🎬'];
        $nav[] = ['teacher/live', 'کلاس آنلاین', '🎥'];
        $nav[] = ['teacher/meetings', 'جلسات', '🤝'];
    }
    if ($t?->hasPermissionTo('homework.view.assigned')) {
        $nav[] = ['teacher/homework', 'تکالیف', '📝'];
    }
    if ($t?->hasPermissionTo('attendance.view.assigned')) {
        $nav[] = ['teacher/attendance', 'حضور و غیاب', '✅'];
    }
    if ($t?->hasPermissionTo('messages.view.assigned')) {
        $nav[] = ['teacher/messages', 'پیام اولیا', '💬'];
    }
    if ($t?->hasPermissionTo('consultations.create')) {
        $nav[] = ['teacher/consultations', 'درخواست مشاوره', '🗨️'];
    }

    if ($t?->hasPermissionTo('resources.manage.assigned')) {
        $nav[] = ['teacher/resources', 'بارگذاری جزوه و منابع', '📚'];
    }
    if ($t?->hasPermissionTo('videos.manage.assigned')) {
        $nav[] = ['teacher/videos', 'بارگذاری فیلم‌ها', '🎞️'];
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
