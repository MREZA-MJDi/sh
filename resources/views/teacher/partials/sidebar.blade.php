@php
    $t = $teacher ?? auth()->user();
    $groups = [
        ['label' => 'نمای کلی', 'items' => [['teacher.dashboard', 'داشبورد', 'home', 'teacher']]],
    ];
    $learning = [];
    if ($t?->hasPermissionTo('classes.view.assigned')) $learning[] = ['teacher.classes', 'کلاس‌ها و دوره‌ها', 'book', 'teacher/classes'];
    if ($t?->hasPermissionTo('classes.view.assigned')) $learning[] = ['teacher.live', 'کلاس آنلاین', 'video', 'teacher/live'];
    if ($t?->hasPermissionTo('classes.view.assigned')) $learning[] = ['teacher.meetings', 'جلسات با اولیا', 'calendar', 'teacher/meetings'];
    if ($learning) $groups[] = ['label' => 'کلاس و برنامه', 'items' => $learning];
    $academic = [];
    if ($t?->hasPermissionTo('homework.view.assigned')) $academic[] = ['teacher.homework', 'تکالیف و ارزیابی', 'check', 'teacher/homework'];
    if ($t?->hasPermissionTo('attendance.view.assigned')) $academic[] = ['teacher.attendance', 'حضور و غیاب', 'check', 'teacher/attendance'];
    if ($t?->hasPermissionTo('resources.manage.assigned')) $academic[] = ['teacher.resources', 'جزوه و منابع', 'file', 'teacher/resources'];
    if ($t?->hasPermissionTo('videos.manage.assigned')) $academic[] = ['teacher.videos', 'ویدئوهای آموزشی', 'video', 'teacher/videos'];
    if ($academic) $groups[] = ['label' => 'محتوای آموزشی', 'items' => $academic];
    $communication = [];
    if ($t?->hasPermissionTo('messages.view.assigned')) $communication[] = ['teacher.messages.index', 'پیام‌ها و گفتگوها', 'message', 'teacher/messages'];
    if ($t?->hasPermissionTo('consultations.create')) $communication[] = ['teacher.consultations.index', 'درخواست هماهنگی', 'message', 'teacher/consultations'];
    if ($communication) $groups[] = ['label' => 'ارتباطات', 'items' => $communication];
@endphp
<aside class="teacher-sidebar" id="teacherSidebar" aria-label="منوی اصلی مدرس" tabindex="-1">
    <a href="{{ route('teacher.profile.edit') }}" class="me teacher-sidebar-identity" title="ویرایش پروفایل و تصویر">
        <span class="av">@if($t?->avatarUrl())<img src="{{ $t->avatarUrl() }}" alt="">@else{{ mb_substr((string) data_get($t, 'name', ''), 0, 1) }}@endif</span>
        <span class="teacher-sidebar-identity-copy"><strong>{{ data_get($t, 'name') }}</strong><small>{{ data_get($t, 'subject') ?: 'مدرس' }}</small></span>
        <span class="teacher-sidebar-profile-indicator" aria-hidden="true">↗</span>
    </a>
    <nav class="teacher-sidebar-nav" aria-label="بخش‌های پنل مدرس">
        @foreach($groups as $group)
            <div class="teacher-sidebar-group"><span class="teacher-sidebar-group-label">{{ $group['label'] }}</span>
                @foreach($group['items'] as [$routeName, $label, $icon, $path])
                    @php $active = request()->routeIs($routeName) || ($path !== 'teacher' && request()->is($path.'/*')); @endphp
                    <a href="{{ route($routeName) }}" class="{{ $active ? 'on' : '' }}" @if($active) aria-current="page" @endif><span class="teacher-sidebar-icon" aria-hidden="true"><x-portal.icon :name="$icon"/></span><span class="teacher-sidebar-link-label">{{ $label }}</span></a>
                @endforeach
            </div>
        @endforeach
    </nav>
    <div class="teacher-sidebar-footer"><span>فضای کاری مدرس</span><small>آکادمی شیخان</small></div>
</aside>