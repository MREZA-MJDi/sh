@php
    $parent = auth()->user();
    $childrenCount = $parent?->children()->where('users.is_active', true)->whereHas('roles', fn ($roles) => $roles->where('name', 'student'))->count() ?? 0;
    $nav = [['guardian.dashboard', 'داشبورد', '🏠', 'parent']];
    if ($parent?->hasPermissionTo('attendance.view.children')) $nav[] = ['guardian.attendance', 'حضور و غیاب', '✅', 'parent/attendance'];
    if ($parent?->hasPermissionTo('exams.view.children')) $nav[] = ['guardian.exams', 'آزمون‌ها و نمرات', '📊', 'parent/exams'];
    if ($parent?->hasPermissionTo('homework.view.children')) $nav[] = ['guardian.homework', 'تکالیف', '📝', 'parent/homework'];
    if ($parent?->hasPermissionTo('resources.view.enrolled')) $nav[] = ['guardian.resources.index', 'منابع آموزشی', '📚', 'parent/resources'];
    if ($parent?->hasPermissionTo('messages.view.children')) $nav[] = ['guardian.messages.index', 'پیام به مدرس', '💬', 'parent/messages'];
    if ($parent?->hasPermissionTo('consultations.view.children') || $parent?->hasPermissionTo('consultations.create')) $nav[] = ['guardian.consultations.index', 'درخواست مشاوره', '🗨️', 'parent/consultations'];
    $nav[] = ['guardian.meetings.index', 'جلسات', '🤝', 'parent/meetings'];
@endphp
<aside class="parent-sidebar" id="parentSidebar" aria-label="منوی اصلی اولیا" tabindex="-1">
    <a class="parent-sidebar-identity" href="{{ route('guardian.dashboard') }}">
        <span class="parent-sidebar-avatar">
            @if($parent?->avatarUrl())<img src="{{ $parent->avatarUrl() }}" alt="">@else{{ mb_substr((string) $parent?->name, 0, 1) }}@endif
        </span>
        <span class="parent-sidebar-identity-copy"><strong>{{ $parent?->name }}</strong><small>{{ $childrenCount }} فرزند متصل</small></span>
    </a>
    <nav class="parent-sidebar-nav" aria-label="بخش‌های پنل اولیا">
        @foreach ($nav as [$routeName, $label, $icon, $path])
            @php $active = request()->routeIs($routeName) || request()->is($path.'/*'); @endphp
            <a href="{{ route($routeName) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif>
                <span class="parent-sidebar-icon" aria-hidden="true">{{ $icon }}</span><span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    <div class="parent-sidebar-footer"><span>پنل خانواده</span><small>آموزشگاه آنلاین</small></div>
</aside>