@php
    $t = $teacher ?? auth()->user();
    $nav = [
        ['teacher', 'داشبورد', '🏠'],
        ['teacher/profile', 'پروفایل و تصویر', '👤'],
    ];
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
    <a href="{{ route('teacher.profile.edit') }}" class="me" style="text-decoration:none;color:inherit;display:flex" title="ویرایش پروفایل و تصویر">
        <div class="av" style="overflow:hidden">
            @if($t?->avatarUrl())
                <img src="{{ $t->avatarUrl() }}" alt="{{ data_get($t, 'name') }}" style="width:100%;height:100%;object-fit:cover;border-radius:50%">
            @else
                {{ mb_substr((string) data_get($t, 'name', ''), 0, 1) }}
            @endif
        </div>
        <div style="flex:1">{{ data_get($t, 'name') }}<small>{{ data_get($t, 'subject') ?: 'مدرس' }}</small></div>
    </a>
    <nav>
        @foreach ($nav as [$path, $label, $icon])
            @php $active = $path === 'teacher' ? request()->is('teacher') : request()->is($path) || request()->is($path.'/*'); @endphp
            <a href="{{ url($path) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif><span>{{ $icon }}</span>{{ $label }}</a>
        @endforeach
    </nav>
</div>
