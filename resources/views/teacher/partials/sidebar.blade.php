@php
    $t = $teacher ?? auth()->user();
    $nav = [
        ['teacher.dashboard', 'داشبورد', '🏠', 'teacher'],
        ['teacher.profile.edit', 'پروفایل و تصویر', '👤', 'teacher/profile'],
    ];
    if ($t?->hasPermissionTo('classes.view.assigned')) {
        $nav[] = ['teacher.classes', 'کلاس‌ها', '🎬', 'teacher/classes'];
        $nav[] = ['teacher.live', 'کلاس آنلاین', '🎥', 'teacher/live'];
        $nav[] = ['teacher.meetings', 'جلسات', '🤝', 'teacher/meetings'];
    }
    if ($t?->hasPermissionTo('homework.view.assigned')) $nav[] = ['teacher.homework', 'تکالیف', '📝', 'teacher/homework'];
    if ($t?->hasPermissionTo('attendance.view.assigned')) $nav[] = ['teacher.attendance', 'حضور و غیاب', '✅', 'teacher/attendance'];
    if ($t?->hasPermissionTo('messages.view.assigned')) $nav[] = ['teacher.messages.index', 'پیام اولیا', '💬', 'teacher/messages'];
    if ($t?->hasPermissionTo('consultations.create')) $nav[] = ['teacher.consultations.index', 'درخواست مشاوره', '🗨️', 'teacher/consultations'];
    if ($t?->hasPermissionTo('resources.manage.assigned')) $nav[] = ['teacher.resources', 'بارگذاری جزوه و منابع', '📚', 'teacher/resources'];
    if ($t?->hasPermissionTo('videos.manage.assigned')) $nav[] = ['teacher.videos', 'بارگذاری فیلم‌ها', '🎞️', 'teacher/videos'];
@endphp
<aside class="teacher-sidebar" id="teacherSidebar" aria-label="منوی اصلی مدرس" tabindex="-1">
    <a href="{{ route('teacher.profile.edit') }}" class="me teacher-sidebar-identity" title="ویرایش پروفایل و تصویر">
        <span class="av">
            @if($t?->avatarUrl())
                <img src="{{ $t->avatarUrl() }}" alt="">
            @else
                {{ mb_substr((string) data_get($t, 'name', ''), 0, 1) }}
            @endif
        </span>
        <span class="teacher-sidebar-identity-copy">
            <strong>{{ data_get($t, 'name') }}</strong>
            <small>{{ data_get($t, 'subject') ?: 'مدرس' }}</small>
        </span>
    </a>
    <nav class="teacher-sidebar-nav" aria-label="بخش‌های پنل مدرس">
        @foreach ($nav as [$routeName, $label, $icon, $path])
            @php $active = request()->routeIs($routeName) || ($path !== 'teacher' && request()->is($path.'/*')); @endphp
            <a href="{{ route($routeName) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif>
                <span class="teacher-sidebar-icon" aria-hidden="true">{{ $icon }}</span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <div class="teacher-sidebar-footer"><span>پنل اختصاصی مدرس</span><small>آکادمی شیخان</small></div>
</aside>